<?php

namespace App\Console\Commands;

use App\Services\SitemapGenerator;
use Illuminate\Console\Command;

class GenerateSitemap extends Command
{
    protected $signature = 'sitemap:generate {--url= : Override canonical URL (e.g. https://eu-vat.info)}';

    protected $description = 'Generate a local copy of the dynamic sitemap index for verification';

    public function handle(SitemapGenerator $generator): int
    {
        if ($url = $this->option('url')) {
            $generator->setBaseUrl($url);
        }

        $path = $generator->writeToFile();
        $count = substr_count(file_get_contents($path), '<url>');

        $this->info("Sitemap written to {$path} ({$count} URLs, base: {$generator->getBaseUrl()})");

        return self::SUCCESS;
    }
}
