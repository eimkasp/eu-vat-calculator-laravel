<?php

use App\Models\Country;
use App\Models\VatRate;
use App\Support\Seo\CountryOgImage;
use Illuminate\Support\Facades\Storage;

/** A renderer that writes a stand-in PNG instead of starting Chrome, and counts its drawings. */
function fakeOgImages(bool $fail = false): CountryOgImage
{
    $images = new class extends CountryOgImage
    {
        public int $drawn = 0;

        public bool $fail = false;

        public ?string $lastHtml = null;

        public function withBrowser(callable $callback): mixed
        {
            return $callback($this);
        }

        protected function screenshot(string $html, string $path): void
        {
            if ($this->fail) {
                throw new RuntimeException('Chrome is not installed');
            }

            $this->drawn++;
            $this->lastHtml = $html;
            file_put_contents($path, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII='));
        }
    };
    $images->fail = $fail;

    app()->instance(CountryOgImage::class, $images);

    return $images;
}

beforeEach(function () {
    Storage::fake('local');

    $this->germany = Country::factory()->create([
        'name' => 'Germany', 'iso_code' => 'DE', 'slug' => 'germany', 'standard_rate' => 19, 'reduced_rate' => '7',
    ]);
    $this->greece = Country::factory()->create([
        'name' => 'Greece', 'iso_code' => 'GR', 'slug' => 'greece', 'standard_rate' => 24, 'reduced_rate' => '6 / 13', 'super_reduced_rate' => 4,
    ]);
});

it('gives the country calculator its own social image in the page language', function () {
    $images = app(CountryOgImage::class);

    $this->get('/vat-calculator/germany')
        ->assertOk()
        ->assertSee('<meta property="og:image" content="'.$images->url($this->germany, 'en').'">', false)
        ->assertSee('<meta name="twitter:image" content="'.$images->url($this->germany, 'en').'">', false)
        ->assertSee('<meta property="og:image:width" content="1200">', false)
        ->assertSee('<meta property="og:image:alt" content="Germany — Standard rate 19%">', false);

    $this->get('/de/vat-calculator/germany')
        ->assertOk()
        ->assertSee('https://vat.businesspress.io/og/de/vat-calculator/germany-', false)
        ->assertSee('<meta property="og:image:alt" content="Deutschland — ', false);
});

it('uses the English image on country pages whose body is English', function () {
    VatRate::create(['country_id' => $this->germany->id, 'type' => 'standard', 'rate' => 19, 'effective_from' => '2021-01-01', 'source' => 'Official archive']);

    $this->get('/de/vat-rates/germany/history')
        ->assertOk()
        ->assertSee('https://vat.businesspress.io/og/en/vat-calculator/germany-', false);
});

it('gives the image a new URL when a rate changes', function () {
    $images = app(CountryOgImage::class);
    $before = $images->version($this->germany, 'en');

    $this->germany->update(['standard_rate' => 20]);

    expect(app(CountryOgImage::class)->version($this->germany->fresh(), 'en'))->not->toBe($before)
        ->and($images->version($this->germany, 'de'))->not->toBe($before);
});

it('draws the image once and lets the CDN keep it for a year', function () {
    $images = fakeOgImages();
    $url = parse_url($images->url($this->germany, 'en'), PHP_URL_PATH);

    $response = $this->get($url)->assertOk()->assertHeader('Content-Type', 'image/png');
    expect($response->headers->get('Cache-Control'))->toContain('public', 'max-age=31536000', 'immutable')
        ->and($response->headers->getCookies())->toBe([]);

    $this->get($url)->assertOk();

    expect($images->drawn)->toBe(1)
        ->and(Storage::disk('local')->files('og-images/countries/en'))->toHaveCount(1);
});

it('keeps only the current version of an image', function () {
    $images = fakeOgImages();
    $images->path($this->germany, 'en');

    $this->germany->update(['standard_rate' => 20]);
    $fresh = fakeOgImages();
    $fresh->path($this->germany->fresh(), 'en');

    expect(Storage::disk('local')->files('og-images/countries/en'))
        ->toBe(['og-images/countries/en/germany-'.$fresh->version($this->germany->fresh(), 'en').'.png']);
});

it('redirects an outdated image URL to the current one', function () {
    $images = fakeOgImages();

    $this->get('/og/en/vat-calculator/germany-0123456789ab.png')
        ->assertMovedPermanently()
        ->assertRedirect(parse_url($images->url($this->germany, 'en'), PHP_URL_PATH));
});

it('answers unknown languages and countries with 404', function (string $path) {
    fakeOgImages();

    $this->get($path)->assertNotFound();
})->with([
    'language' => ['/og/xx/vat-calculator/germany-0123456789ab.png'],
    'country' => ['/og/en/vat-calculator/atlantis-0123456789ab.png'],
]);

it('serves the default image for a few minutes when Chrome fails', function () {
    $images = fakeOgImages(fail: true);

    $response = $this->get(parse_url($images->url($this->germany, 'en'), PHP_URL_PATH))->assertOk();

    expect($response->headers->get('Cache-Control'))->toContain('max-age=300')
        ->and($response->getFile()->getPathname())->toBe(public_path('images/og-default.png'))
        ->and(Storage::disk('local')->allFiles())->toBe([]);
});

it('draws the image from local data only, in the requested language', function () {
    $html = app(CountryOgImage::class)->html($this->greece, 'el');

    expect($html)
        ->toContain('<html lang="el">', 'Ελλάδα', 'Κανονικός συντελεστής', '<span class="n">24</span>', '6% / 13%', 'data:font/woff2;base64,', 'data:image/svg+xml;base64,')
        ->not->toMatch('/(src|href)="https?:|url\(https?:/');
});

it('marks the country in the rate field and gives a country outside the EU a bar', function () {
    $norway = Country::factory()->create(['name' => 'Norway', 'iso_code' => 'NO', 'slug' => 'norway', 'standard_rate' => 25, 'is_eu_member' => false]);
    $images = app(CountryOgImage::class);

    expect(implode("\n", CountryOgImage::field($images->euRates(), $this->germany)))->toContain('<b>D')
        ->and(implode("\n", CountryOgImage::field($images->euRates(), $norway)))->toContain('<b>N');
});

it('draws the missing images ahead of the crawlers', function () {
    $images = fakeOgImages();
    $images->path($this->germany, 'en');

    $this->artisan('og-image:countries', ['--locale' => ['en', 'de']])
        ->expectsOutputToContain('Drew 3 of 3 images.')
        ->assertSuccessful();

    $this->artisan('og-image:countries', ['--locale' => ['en', 'de']])
        ->expectsOutputToContain('Every country image is drawn already.')
        ->assertSuccessful();

    expect($images->drawn)->toBe(4);
});
