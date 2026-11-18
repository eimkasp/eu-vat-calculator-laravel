<?php

namespace App\Console\Commands;

use App\Models\Country;
use App\Support\Seo\CountryOgImage;
use App\Support\Seo\SeoPolicy;
use Illuminate\Console\Command;
use Throwable;

class WarmCountryOgImages extends Command
{
    protected $signature = 'og-image:countries
        {--locale=* : Only these languages (default: every indexable language)}
        {--country=* : Only these country slugs}';

    protected $description = 'Draw the missing social images of the country pages, so no crawler waits for Chrome';

    public function handle(CountryOgImage $images, SeoPolicy $seo): int
    {
        $locales = $this->option('locale') ?: $seo->indexableLocales();
        $countries = Country::calculatorAvailable()
            ->when($this->option('country'), fn ($query, array $slugs) => $query->whereIn('slug', $slugs))
            ->orderBy('slug')
            ->get();

        $missing = $countries->crossJoin($locales)
            ->reject(fn (array $pair) => $images->exists($pair[0], $pair[1]))
            ->values();

        if ($missing->isEmpty()) {
            $this->info('Every country image is drawn already.');

            return self::SUCCESS;
        }

        $failed = 0;

        try {
            $images->withBrowser(function (CountryOgImage $images) use ($missing, &$failed) {
                $this->withProgressBar($missing, function (array $pair) use ($images, &$failed) {
                    try {
                        $images->path($pair[0], $pair[1]);
                    } catch (Throwable $e) {
                        $failed++;
                        report($e);
                    }
                });
            });
        } catch (Throwable $e) {
            report($e);
            $this->error('Chrome could not be started: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->newLine();
        $this->info(sprintf('Drew %d of %d images.', $missing->count() - $failed, $missing->count()));

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }
}
