<?php

namespace App\Support\Seo;

use App\Models\Country;
use Backstage\OgImage\Laravel\Facades\OgImage;
use Carbon\CarbonImmutable;
use HeadlessChromium\Browser;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Throwable;

/**
 * The social image of a country page: resources/og/country.blade.php drawn by headless Chrome
 * through backstage/laravel-og-image, one PNG per country and language.
 *
 * The URL carries a version worked out from everything the image shows, so a rate change gives
 * a new URL (social networks cache images by URL) and each file can be cached for a year.
 */
class CountryOgImage
{
    /** Bump when resources/og/country.blade.php changes, so every image is drawn again. */
    public const TEMPLATE_VERSION = 1;

    public const WIDTH = 1200;

    public const HEIGHT = 630;

    private ?Browser $browser = null;

    private int $drawnWithBrowser = 0;

    /** @var list<array{iso: string, rate: float}>|null */
    private ?array $euRates = null;

    /**
     * The image URL for a country page. Without a locale it follows the page: its language when
     * the body is translated, English otherwise.
     */
    public function url(Country $country, ?string $locale = null): string
    {
        $locale ??= $this->pageLocale();

        return app(SeoPolicy::class)->canonicalizeLocalUrl(route('og-image.country', [
            'locale' => $locale,
            'slug' => $country->slug,
            'version' => $this->version($country, $locale),
        ]));
    }

    public function version(Country $country, string $locale): string
    {
        return substr(hash('sha256', json_encode([
            self::TEMPLATE_VERSION,
            $locale,
            $country->iso_code,
            $country->apiRates(),
            $country->updated_at?->toDateString(),
            $this->euRates(),
        ])), 0, 12);
    }

    /** The alternative text of the image, in the image's language. */
    public function alt(Country $country, ?string $locale = null): string
    {
        $locale ??= $this->pageLocale();
        $previous = app()->getLocale();
        app()->setLocale($locale);

        try {
            return self::countryName($country).' — '.__('ui.history.standard_rate').' '.Country::formatPercent($country->standard_rate);
        } finally {
            app()->setLocale($previous);
        }
    }

    /**
     * The image file, drawn first when it is missing. Returns the absolute path.
     */
    public function path(Country $country, string $locale): string
    {
        $relative = $this->relativePath($country, $locale);
        $disk = $this->disk();

        if (! $disk->exists($relative)) {
            Cache::lock('og-image:'.$relative, 60)->block(45, function () use ($country, $locale, $relative, $disk) {
                if ($disk->exists($relative)) {
                    return;
                }

                $disk->makeDirectory(dirname($relative));
                $this->screenshot($this->html($country, $locale), $disk->path($relative));
                $this->deleteOtherVersions($disk, $relative);
            });
        }

        return $disk->path($relative);
    }

    public function exists(Country $country, string $locale): bool
    {
        return $this->disk()->exists($this->relativePath($country, $locale));
    }

    /**
     * Draw many images with one browser instead of launching Chrome for each.
     */
    public function withBrowser(callable $callback): mixed
    {
        $this->browser = OgImage::getBrowser();

        try {
            return $callback($this);
        } finally {
            try {
                OgImage::releaseBrowser($this->browser);
            } finally {
                $this->browser = null;
                $this->drawnWithBrowser = 0;
            }
        }
    }

    /** The HTML Chrome draws, in the given language. */
    public function html(Country $country, string $locale): string
    {
        $previous = app()->getLocale();
        app()->setLocale($locale);

        try {
            return View::file(resource_path('og/country.blade.php'), $this->viewData($country))->render();
        } finally {
            app()->setLocale($previous);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function viewData(Country $country): array
    {
        $euRates = $this->euRates();
        $rates = $country->apiRates();
        $isMember = (bool) $country->is_eu_member;
        $average = $euRates === [] ? null : array_sum(array_column($euRates, 'rate')) / count($euRates);
        $updated = $country->updated_at ? CarbonImmutable::parse($country->updated_at) : null;
        $name = self::countryName($country);

        return [
            'name' => $name,
            'nameSize' => match (true) {
                mb_strlen($name) > 22 => 52,
                mb_strlen($name) > 14 => 64,
                default => 76,
            },
            'flag' => self::flagDataUri($country->iso_code),
            'standard' => Country::formatNumber($rates['standard'], self::decimals($rates['standard'])),
            'facts' => [
                ['label' => __('ui.rate_type.reduced'), 'value' => $rates['reduced_rates'] === [] ? null : implode(' / ', array_map(fn (float $rate) => Country::formatPercent($rate), $rates['reduced_rates']))],
                ['label' => __('ui.rate_type.super_reduced'), 'value' => $rates['super_reduced'] ? Country::formatPercent($rates['super_reduced']) : null],
                ['label' => __('ui.rate_type.parking'), 'value' => $rates['parking'] ? Country::formatPercent($rates['parking']) : null],
                ['label' => __('ui.home_page.stat_average'), 'value' => $average === null ? null : Country::formatPercent(round($average, 1))],
            ],
            'updated' => $updated ? __('ui.trust.updated', ['date' => $updated->locale(app()->getLocale())->translatedFormat('j M Y')]) : null,
            'source' => $isMember ? __('ui.trust.source_ec') : __('ui.trust.source_dataset'),
            'host' => parse_url(app(SeoPolicy::class)->canonicalHost(), PHP_URL_HOST),
            'field' => self::field($euRates, $country),
            'fonts' => self::fontFaces(),
        ];
    }

    /** The country's name in the current language (CLDR), falling back to the stored English name. */
    public static function countryName(Country $country): string
    {
        $iso = strtoupper((string) $country->iso_code) === 'EL' ? 'GR' : strtoupper((string) $country->iso_code);
        $local = class_exists(\Locale::class) ? (string) \Locale::getDisplayRegion('-'.$iso, app()->getLocale()) : '';
        $name = $local !== '' && $local !== $iso ? $local : (string) $country->name;

        return mb_strtoupper(mb_substr($name, 0, 1)).mb_substr($name, 1);
    }

    /**
     * The rate field: one bar of glyphs per member state, as tall as its standard rate, lowest
     * first, over a faint glyph texture. The country's own bar is cobalt and carries its code;
     * a country outside the EU gets a bar of its own.
     *
     * @param  list<array{iso: string, rate: float}>  $euRates
     * @return list<string> one HTML-safe line per row
     */
    public static function field(array $euRates, Country $country): array
    {
        $iso = strtoupper((string) $country->iso_code);
        $bars = collect($euRates);

        if (! $bars->contains('iso', $iso)) {
            $bars->push(['iso' => $iso, 'rate' => (float) $country->standard_rate]);
        }

        $bars = $bars->sortBy([['rate', 'asc'], ['iso', 'asc']])->values();
        $rows = 38;
        $cols = max(1, $bars->count() * 3 - 1);
        $floor = max(0.0, min(10.0, (float) $bars->min('rate') - 6));
        $ceiling = max(28.0, (float) $bars->max('rate'));
        $ramp = ' .:-=+*#%@';
        $grid = [];

        for ($r = 0; $r < $rows; $r++) {
            for ($c = 0; $c < $cols; $c++) {
                $noise = (sin($c * .37) * cos($r * .53) + sin(($c + $r) * .11)) * .5 + .5;
                $grid[$r][$c] = ['glyph' => $noise > .84 ? ($noise > .94 ? ':' : '.') : ' ', 'class' => null];
            }
        }

        $tops = [];

        foreach ($bars as $index => $bar) {
            $height = max(1, (int) round(($rows - 3) * (max(0, $bar['rate'] - $floor) / ($ceiling - $floor))));
            $top = $tops[$index] = $rows - $height;
            $own = $bar['iso'] === $iso;

            for ($r = $top; $r < $rows; $r++) {
                $depth = ($r - $top) / max(1, $height - 1);
                $glyph = $ramp[(int) round((1 - $depth * .55) * (strlen($ramp) - 1))];

                foreach ([0, 1] as $offset) {
                    $grid[$r][$index * 3 + $offset] = ['glyph' => $glyph, 'class' => $own ? 'b' : 'i'];
                }
            }

            if ($own && $top > 1) {
                foreach (str_split(str_pad(substr($iso, 0, 2), 2)) as $offset => $letter) {
                    $grid[$top - 2][$index * 3 + $offset] = ['glyph' => $letter, 'class' => 'b'];
                }
            }
        }

        // Keep the gaps between bars clear, so each bar reads on its own.
        foreach (array_keys($tops) as $index) {
            $gap = $index * 3 + 2;

            for ($r = min($tops[$index], $tops[$index + 1] ?? $tops[$index]); $gap < $cols && $r < $rows; $r++) {
                $grid[$r][$gap] = ['glyph' => ' ', 'class' => null];
            }
        }

        return array_map(function (array $cells) {
            $line = '';
            $open = null;

            foreach ($cells as $cell) {
                if ($cell['class'] !== $open) {
                    $line .= ($open ? '</'.$open.'>' : '').($cell['class'] ? '<'.$cell['class'].'>' : '');
                    $open = $cell['class'];
                }

                $line .= e($cell['glyph']);
            }

            return $line.($open ? '</'.$open.'>' : '');
        }, $grid);
    }

    /**
     * Every member state's standard rate, read once per request.
     *
     * @return list<array{iso: string, rate: float}>
     */
    public function euRates(): array
    {
        return $this->euRates ??= Country::forMap()
            ->filter(fn (Country $country) => $country->is_eu_member)
            ->map(fn (Country $country) => ['iso' => strtoupper((string) $country->iso_code), 'rate' => (float) $country->standard_rate])
            ->sortBy('iso')
            ->values()
            ->all();
    }

    private function pageLocale(): string
    {
        return app(SeoPolicy::class)->hasTranslatedBody() ? app()->getLocale() : (string) config('translation.default_language', 'en');
    }

    private function relativePath(Country $country, string $locale): string
    {
        return trim((string) config('og-image.storage.path'), '/').'/countries/'.$locale.'/'.$country->slug.'-'.$this->version($country, $locale).'.png';
    }

    private function disk(): Filesystem
    {
        return Storage::disk(config('og-image.storage.disk'));
    }

    private function deleteOtherVersions(Filesystem $disk, string $current): void
    {
        $prefix = preg_replace('/-[a-f0-9]{12}\.png$/', '-', basename($current));

        foreach ($disk->files(dirname($current)) as $file) {
            if ($file !== $current && preg_match('/^'.preg_quote($prefix, '/').'[a-f0-9]{12}\.png$/', basename($file))) {
                $disk->delete($file);
            }
        }
    }

    /**
     * Load the HTML into a page of the shared browser (or one launched for this image), wait
     * for its fonts and write the screenshot.
     */
    protected function screenshot(string $html, string $path): void
    {
        if (! $this->browser) {
            $browser = OgImage::getBrowser();

            try {
                $this->draw($browser, $html, $path);
            } finally {
                OgImage::releaseBrowser($browser);
            }

            return;
        }

        // A shared browser that has drawn many images can stop answering; start a fresh one
        // every so often, and once more when a drawing fails.
        if (++$this->drawnWithBrowser > 40) {
            $this->restartBrowser();
        }

        try {
            $this->draw($this->browser, $html, $path);
        } catch (Throwable $e) {
            $this->restartBrowser();
            $this->draw($this->browser, $html, $path);
        }
    }

    private function draw(Browser $browser, string $html, string $path): void
    {
        $page = $browser->createPage();

        try {
            $page->setViewport(self::WIDTH, self::HEIGHT)->await();
            $page->setHtml($html, 15000);
            $page->evaluate('document.fonts.ready.then(() => true)')->getReturnValue(10000);
            $page->screenshot(['format' => 'png'])->saveToFile($path);
        } finally {
            try {
                $page->close();
            } catch (Throwable) {
                // The image is written; a tab that will not close goes with the browser.
            }
        }
    }

    private function restartBrowser(): void
    {
        try {
            OgImage::releaseBrowser($this->browser);
        } catch (Throwable) {
            // Replaced below either way.
        }

        $this->browser = OgImage::getBrowser();
        $this->drawnWithBrowser = 0;
    }

    private static function decimals(float $rate): int
    {
        return fmod($rate, 1.0) === 0.0 ? 0 : 1;
    }

    private static function flagDataUri(?string $iso): ?string
    {
        $code = strtolower((string) $iso);
        $code = $code === 'el' ? 'gr' : ($code === 'uk' ? 'gb' : $code);
        $file = public_path('images/flags/'.$code.'.svg');

        return preg_match('/^[a-z]{2}$/', $code) && is_file($file)
            ? 'data:image/svg+xml;base64,'.base64_encode((string) file_get_contents($file))
            : null;
    }

    /**
     * The fonts, inlined: Chrome draws the HTML from memory, so it cannot fetch them from the site.
     */
    private static function fontFaces(): string
    {
        static $css = null;

        if ($css !== null) {
            return $css;
        }

        $latin = 'U+0000-00FF,U+0131,U+0152-0153,U+02BB-02BC,U+02C6,U+02DA,U+02DC,U+0304,U+0308,U+0329,U+2000-206F,U+20AC,U+2122,U+2191,U+2193,U+2212,U+2215,U+FEFF,U+FFFD';
        $latinExt = 'U+0100-02BA,U+02BD-02C5,U+02C7-02CC,U+02CE-02D7,U+02DD-02FF,U+0304,U+0308,U+0329,U+1D00-1DBF,U+1E00-1E9F,U+1EF2-1EFF,U+2020,U+20A0-20AB,U+20AD-20C0,U+2113,U+2C60-2C7F,U+A720-A7FF';
        $faces = [
            ['Familjen', 'familjen-grotesk-latin-wght-normal', '400 700', $latin],
            ['Familjen', 'familjen-grotesk-latin-ext-wght-normal', '400 700', $latinExt],
            ['Plex', 'ibm-plex-sans-latin-wght-normal', '100 700', $latin],
            ['Plex', 'ibm-plex-sans-latin-ext-wght-normal', '100 700', $latinExt],
            ['Plex', 'ibm-plex-sans-cyrillic-wght-normal', '100 700', 'U+0301,U+0400-045F,U+0490-0491,U+04B0-04B1,U+2116'],
            ['Plex', 'ibm-plex-sans-greek-wght-normal', '100 700', 'U+0370-0377,U+037A-037F,U+0384-038A,U+038C,U+038E-03A1,U+03A3-03FF'],
            ['JBMono', 'jetbrains-mono-latin-wght-normal', '100 800', $latin],
            ['JBMono', 'jetbrains-mono-latin-ext-wght-normal', '100 800', $latinExt],
        ];

        return $css = collect($faces)->map(fn (array $face) => sprintf(
            "@font-face{font-family:%s;src:url(data:font/woff2;base64,%s) format('woff2');font-weight:%s;unicode-range:%s}",
            $face[0],
            base64_encode((string) file_get_contents(resource_path('og/fonts/'.$face[1].'.woff2'))),
            $face[2],
            $face[3],
        ))->implode("\n");
    }
}
