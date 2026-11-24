<?php

namespace App\Support\EntityMap;

use App\Models\Country;
use App\Support\Mcp\VatMcpServer;
use App\Support\Seo\DatasetSchema;
use App\Support\Seo\SeoPolicy;

/**
 * The entity map: every thing EU VAT Info is and offers, defined once, each backed by passages quoted from
 * the site's own pages. The page at /entity-map renders it and `entity-map:verify` checks every passage
 * against the live page it cites, so a definition cannot drift from the site without a failing check.
 */
class EntityMap
{
    public const SECTIONS = [
        'organization' => 'Organisation',
        'services' => 'Services',
        'terms' => 'Prices and terms',
        'concepts' => 'Terms we use',
        'sources' => 'Sources',
    ];

    /**
     * @return list<array{id: string, section: string, type: string, name: string, definition: string, url: string, passages: list<array{text: string, url: string}>}>
     */
    public static function entities(): array
    {
        $mcp = VatMcpServer::endpoint();
        $tools = implode(', ', VatMcpServer::toolNames());

        return [
            self::entity('eu-vat-info', 'organization', 'Organization', 'EU VAT Info', 'EU VAT Info (eu-vat.info) publishes current and historical VAT rates, a VAT calculator, VIES VAT number validation and machine-readable VAT data for the 27 EU member states and five other European countries.', '/', [
                ['Free to use without sign-up. Rates come from European Commission data and are updated every week.', '/llms.txt'],
            ]),

            self::entity('vat-calculator', 'services', 'WebApplication', 'EU VAT calculator', 'Adds VAT to a net amount or removes it from a gross amount for any EU country, at the standard, reduced, super-reduced or parking rate.', '/vat-calculator', [
                ['Add or remove VAT for any EU country.', '/llms.txt'],
            ]),
            self::entity('vat-number-validator', 'services', 'WebApplication', 'VAT number validator', 'Checks an EU VAT number against the European Commission\'s VIES service and shows whether it is registered.', '/vat-number-validator', [
                ['Check an EU VAT number against the European Commission\'s VIES service.', '/llms.txt'],
            ]),
            self::entity('vat-rate-changes', 'services', 'WebPage', 'VAT rate changes', 'A timeline of recorded and upcoming VAT rate changes for every member state, each with the old and new rate and its official source.', '/vat-changes', [
                ['Recorded and upcoming rate changes with their official sources.', '/llms.txt'],
            ]),
            self::entity('rates-api', 'services', 'WebAPI', 'REST API', 'A JSON API for every EU VAT rate and a VAT calculator, at /api/v1, described by an OpenAPI 3.1 document. No API key is needed.', '/vat-validation-api', [
                ['Every EU VAT rate and a calculator as JSON, with no API key.', '/vat-validation-api'],
            ]),
            self::entity('validation-api', 'services', 'WebAPI', 'VAT number validation API', 'A REST API that validates one VAT number or a batch of up to 10 against the official VIES database.', '/vat-validation-api', [
                ['Validate EU VAT numbers programmatically against the official VIES database. Free to use, no API key and CORS enabled.', '/vat-validation-api'],
            ]),
            self::entity('mcp-server', 'services', 'WebAPI', 'MCP server', "A Model Context Protocol server at {$mcp} that lets Claude, ChatGPT, VS Code, Cursor or any MCP client read live VAT data. Tools: {$tools}.", '/mcp-server', [
                ['Connect Claude, ChatGPT, VS Code, Cursor or any MCP client to live VAT data', '/llms.txt'],
                ['Streamable HTTP, no sign-up', '/llms.txt'],
            ]),
            self::entity('vat-dataset', 'services', 'Dataset', 'EU VAT rates dataset', 'Every EU rate as CSV, JSON or Markdown, with the country, ISO code, standard, reduced, super-reduced and parking rates, currency and update time.', '/datasets/eu-vat-rates', [
                ['Each row includes the country, ISO code, standard rate, available reduced-rate fields, currency and record update time.', '/datasets/eu-vat-rates'],
            ]),
            self::entity('chrome-extension', 'services', 'SoftwareApplication', 'EU VAT calculator for Chrome', 'A free Chrome extension that calculates EU VAT from the browser toolbar with every EU country rate and a calculation history.', '/chrome-extension', [
                ['Calculate EU VAT from your browser toolbar.', '/chrome-extension'],
            ]),

            self::entity('price', 'terms', 'Offer', 'Price', 'Everything on EU VAT Info is free: the site, the API and the MCP server need no account or API key. Donations are optional.', '/donate', [
                ['Free, open-source EU VAT data for developers, businesses and AI agents.', '/donate'],
            ]),
            self::entity('data-licence', 'terms', 'CreativeWork', 'Data licence', 'The rates dataset is licensed CC BY 4.0: use it freely and credit EU VAT Info.', '/datasets/eu-vat-rates', [
                ['CC BY 4.0', '/datasets/eu-vat-rates'],
            ]),
            self::entity('fair-use', 'terms', 'CreativeWork', 'Fair use', sprintf('The MCP server allows %d requests a minute per IP address, including up to %d VAT number checks.', VatMcpServer::REQUESTS_PER_MINUTE, VatMcpServer::VIES_CHECKS_PER_MINUTE), '/mcp-server', []),
            self::entity('not-tax-advice', 'terms', 'CreativeWork', 'Not tax advice', 'VAT treatment depends on the goods or services and the customer, so the rates are reference data rather than tax advice.', '/llms.txt', [
                ['VAT treatment depends on the goods or services and the customer, so use these rates as reference data rather than tax advice.', '/llms.txt'],
            ]),

            self::entity('standard-rate', 'concepts', 'DefinedTerm', 'Standard VAT rate', 'The default rate for most goods and services.', '/llms-full.txt', [
                ['Standard: the default rate for most goods and services.', '/llms-full.txt'],
            ]),
            self::entity('reduced-rate', 'concepts', 'DefinedTerm', 'Reduced VAT rate', 'A lower rate that applies to listed goods and services such as food, books or medicines. Some countries have two.', '/llms-full.txt', [
                ['Reduced: lower rates that apply to listed goods and services such as food, books or medicines. Some countries have two.', '/llms-full.txt'],
            ]),
            self::entity('super-reduced-rate', 'concepts', 'DefinedTerm', 'Super-reduced VAT rate', 'A rate below 5% that some member states keep for specific items.', '/llms-full.txt', [
                ['Super-reduced: a rate below 5% that some member states keep for specific items.', '/llms-full.txt'],
            ]),
            self::entity('parking-rate', 'concepts', 'DefinedTerm', 'Parking VAT rate', 'A transitional rate of at least 12% that a few member states still apply to certain items.', '/llms-full.txt', [
                ['Parking: a transitional rate of at least 12% that a few member states still apply to certain items.', '/llms-full.txt'],
            ]),
            self::entity('vat-prefix', 'concepts', 'DefinedTerm', 'VAT prefix', 'The two letters that start a VAT number in VIES. Greece uses EL.', '/llms-full.txt', [
                ['VAT prefix: the two letters that start a VAT number in VIES. Greece uses EL.', '/llms-full.txt'],
            ]),
            self::entity('vat-number-format', 'concepts', 'DefinedTerm', 'VAT number format', 'Each member state\'s mask in the Commission\'s notation, where 9 is a digit, L a letter and X a letter or a digit. A number that fits can still be unregistered, so check it in VIES.', '/llms-full.txt', [
                ['A number that fits can still be unregistered, so check it in VIES.', '/llms-full.txt'],
            ]),

            self::entity('source-commission', 'sources', 'WebPage', 'European Commission: VAT rates', 'The framework for EU VAT rates and the Taxes in Europe Database the member states report to.', (string) config('calculator.rates_source_url'), [
                ['The framework for EU VAT rates and the Taxes in Europe Database the member states report to.', '/llms.txt'],
            ]),
            self::entity('source-your-europe', 'sources', 'WebPage', 'Your Europe: VAT rules and rates', 'The Commission\'s table of standard, reduced, super-reduced and parking rates, which decides where sources disagree.', 'https://europa.eu/youreurope/business/finance-and-tax/vat/vat-rules-rates/index_en.htm', [
                ['which decides where sources disagree', '/llms.txt'],
            ]),
            self::entity('source-open-dataset', 'sources', 'Dataset', 'Open vat-rates dataset', 'The standard rate history, synced every week, and the rates of the countries outside the EU.', (string) config('calculator.dataset_source_url'), [
                ['The standard rate history, synced every week, and the rates of the countries outside the EU.', '/llms.txt'],
            ]),
            self::entity('source-vies', 'sources', 'WebPage', 'VIES', 'The Commission\'s register behind every VAT number check.', 'https://ec.europa.eu/taxation_customs/vies/', [
                ['The Commission\'s register behind every VAT number check.', '/llms.txt'],
            ]),
        ];
    }

    /**
     * Every passage with the path it must appear on.
     *
     * @return list<array{entity: string, text: string, url: string}>
     */
    public static function passages(): array
    {
        $passages = [];

        foreach (self::entities() as $entity) {
            foreach ($entity['passages'] as $passage) {
                $passages[] = ['entity' => $entity['id'], ...$passage];
            }
        }

        return $passages;
    }

    /**
     * The schema.org graph for the page: one node per entity, its @id the anchor on /entity-map.
     *
     * @return list<array<string, mixed>>
     */
    public static function graph(): array
    {
        $base = app(SeoPolicy::class)->canonicalHost();
        $page = $base.'/entity-map';
        $organization = ['@id' => $base.'/#organization'];

        $about = collect(self::entities())->firstWhere('id', 'eu-vat-info');

        $nodes = [[
            '@type' => 'Organization',
            '@id' => $organization['@id'],
            'name' => $about['name'],
            'url' => $base.'/',
            'description' => $about['definition'],
            'sameAs' => [
                'https://github.com/eimkasp/eu-vat.info',
                'https://chromewebstore.google.com/detail/eu-vat-calculator/fifmbbpgopnifnoginhmjjedjnabdkka',
            ],
        ], [
            '@type' => 'WebPage',
            '@id' => $page,
            'url' => $page,
            'name' => 'EU VAT Info entity map',
            'about' => $organization,
            'isPartOf' => ['@id' => $base.'/#website'],
            'dateModified' => DatasetSchema::ratesModified()->toIso8601String(),
            'inLanguage' => 'en',
        ], [
            '@type' => 'DefinedTermSet',
            '@id' => $page.'#terms',
            'name' => 'EU VAT Info terms',
            'hasDefinedTerm' => array_values(array_map(
                fn (array $entity) => ['@id' => $page.'#'.$entity['id']],
                array_filter(self::entities(), fn (array $entity) => $entity['type'] === 'DefinedTerm'),
            )),
        ]];

        foreach (self::entities() as $entity) {
            if ($entity['id'] === 'eu-vat-info') {
                continue;
            }

            $node = [
                '@type' => $entity['type'],
                '@id' => $page.'#'.$entity['id'],
                'name' => $entity['name'],
                'description' => $entity['definition'],
                'url' => str_starts_with($entity['url'], 'http') ? $entity['url'] : $base.$entity['url'],
            ];

            match ($entity['type']) {
                'DefinedTerm' => $node['inDefinedTermSet'] = ['@id' => $page.'#terms'],
                'Offer' => $node += ['price' => '0', 'priceCurrency' => 'EUR', 'seller' => $organization],
                'WebApplication', 'SoftwareApplication' => $node += ['applicationCategory' => 'BusinessApplication', 'operatingSystem' => 'Any', 'isAccessibleForFree' => true, 'provider' => $organization],
                'WebAPI' => $node += ['provider' => $organization, 'isAccessibleForFree' => true],
                default => null,
            };

            if ($entity['id'] === 'vat-dataset') {
                $node = DatasetSchema::rates();
            }

            $nodes[] = $node;
        }

        return $nodes;
    }

    /**
     * @return array<int, Country>
     */
    public static function countries(): array
    {
        return Country::query()->where('is_eu_member', true)->orderBy('name')->get()->all();
    }

    /**
     * @param  list<array{0: string, 1: string}>  $passages
     */
    private static function entity(string $id, string $section, string $type, string $name, string $definition, string $url, array $passages): array
    {
        return [
            'id' => $id,
            'section' => $section,
            'type' => $type,
            'name' => $name,
            'definition' => $definition,
            'url' => $url,
            'passages' => array_map(fn (array $passage) => ['text' => $passage[0], 'url' => $passage[1]], $passages),
        ];
    }
}
