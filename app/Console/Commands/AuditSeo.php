<?php

namespace App\Console\Commands;

use App\Services\SitemapGenerator;
use App\Support\Seo\SeoPolicy;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;

class AuditSeo extends Command
{
    protected $signature = 'seo:audit';

    protected $description = 'Audit canonical domain, discovery files, locale readiness, routes, and sitemap structure';

    public function handle(SeoPolicy $policy, SitemapGenerator $sitemaps): int
    {
        $errors = [];
        $canonical = $policy->canonicalHost();

        if ($canonical !== 'https://eu-vat.info') {
            $errors[] = "Canonical URL must be https://eu-vat.info; found {$canonical}.";
        }

        $robots = file_get_contents(public_path('robots.txt')) ?: '';
        if (substr_count($robots, 'Sitemap:') !== 1 || ! str_contains($robots, 'Sitemap: https://eu-vat.info/sitemap.xml')) {
            $errors[] = 'robots.txt must declare exactly one canonical sitemap.';
        }

        if (str_contains($robots, 'vat.businesspress.io') || str_contains($robots, 'Crawl-delay:')) {
            $errors[] = 'robots.txt contains a legacy host or unsupported crawl-delay directive.';
        }

        foreach (['sitemap.xml', 'llms.txt'] as $staleFile) {
            if (is_file(public_path($staleFile))) {
                $errors[] = "public/{$staleFile} shadows the dynamic canonical endpoint.";
            }
        }

        $requiredRoutes = [
            'sitemap',
            'sitemap.section',
            'vat-dataset',
            'vat-dataset.csv',
            'vat-rates.country-history',
            'vat-changes.event',
            'vat-changes.year',
            'vat-changes.upcoming',
            'vat-comparison',
        ];

        foreach ($requiredRoutes as $route) {
            if (! Route::has($route)) {
                $errors[] = "Required SEO route is missing: {$route}.";
            }
        }

        foreach ($policy->indexableLocales() as $locale) {
            if (! array_key_exists($locale, config('translation.supported_languages', []))) {
                $errors[] = "Indexable locale is unsupported: {$locale}.";
            }

            $file = lang_path($locale.'/ui.php');
            if (! is_file($file)) {
                $errors[] = "Indexable locale has no UI translations: {$locale}.";
            }
        }

        $index = $sitemaps->generateIndex();
        if (str_contains($index, 'vat.businesspress.io') || ! str_contains($index, '<sitemapindex')) {
            $errors[] = 'Generated sitemap index is not canonical.';
        }

        if ($errors !== []) {
            foreach ($errors as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $this->info('SEO audit passed: canonical host, discovery files, locales, routes, and sitemap structure are valid.');

        return self::SUCCESS;
    }
}
