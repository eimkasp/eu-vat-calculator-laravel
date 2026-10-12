<?php

namespace App\Support\Seo;

use App\Models\Country;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * The schema.org Dataset node for the open rates dataset, defined once so every page that describes it agrees.
 */
class DatasetSchema
{
    public static function rates(?CarbonInterface $modified = null): array
    {
        $base = app(SeoPolicy::class)->canonicalHost();
        $modified ??= self::ratesModified();
        $url = $base.'/datasets/eu-vat-rates';

        return [
            '@type' => 'Dataset',
            '@id' => $url.'#dataset',
            'name' => 'EU VAT Rates Dataset',
            'description' => 'Current standard, reduced, super-reduced and parking VAT rates for the 27 European Union member states, from European Commission data and updated every week.',
            'url' => $url,
            'dateModified' => $modified->toIso8601String(),
            'license' => 'https://creativecommons.org/licenses/by/4.0/',
            'isAccessibleForFree' => true,
            'spatialCoverage' => ['@type' => 'Place', 'name' => 'European Union'],
            'temporalCoverage' => $modified->toDateString(),
            'isBasedOn' => [
                'https://taxation-customs.ec.europa.eu/taxation/vat_en',
                'https://github.com/kdeldycke/vat-rates',
            ],
            'creator' => ['@type' => 'Organization', '@id' => $base.'/#organization', 'name' => 'EU VAT Info', 'url' => $base.'/'],
            'variableMeasured' => [
                ['@type' => 'PropertyValue', 'name' => 'Standard VAT rate', 'unitText' => 'percent'],
                ['@type' => 'PropertyValue', 'name' => 'Reduced VAT rate', 'unitText' => 'percent'],
                ['@type' => 'PropertyValue', 'name' => 'Super-reduced VAT rate', 'unitText' => 'percent'],
                ['@type' => 'PropertyValue', 'name' => 'Parking VAT rate', 'unitText' => 'percent'],
            ],
            'distribution' => [
                ['@type' => 'DataDownload', 'encodingFormat' => 'text/csv', 'contentUrl' => $base.'/datasets/eu-vat-rates.csv'],
                ['@type' => 'DataDownload', 'encodingFormat' => 'application/json', 'contentUrl' => $base.'/datasets/eu-vat-rates.json'],
                ['@type' => 'DataDownload', 'encodingFormat' => 'text/markdown', 'contentUrl' => $base.'/llms-full.txt'],
            ],
        ];
    }

    public static function ratesModified(): CarbonImmutable
    {
        $latest = Country::query()->where('is_eu_member', true)->max('updated_at');

        return $latest ? CarbonImmutable::parse($latest) : now()->startOfDay()->toImmutable();
    }
}
