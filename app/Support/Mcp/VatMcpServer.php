<?php

namespace App\Support\Mcp;

use stdClass;

/**
 * The one description of the public MCP server, shared by the JSON-RPC endpoint,
 * the discovery documents and the /mcp-server page so they never drift apart.
 */
final class VatMcpServer
{
    public const NAME = 'eu-vat-info';

    public const TITLE = 'EU VAT Info';

    public const VERSION = '1.1.0';

    /** Newest first; the first entry is offered when a client asks for a version this server does not speak. */
    public const PROTOCOL_VERSIONS = ['2025-06-18', '2025-03-26', '2024-11-05'];

    public const REQUESTS_PER_MINUTE = 120;

    public const VIES_CHECKS_PER_MINUTE = 20;

    public static function endpoint(): string
    {
        return rtrim((string) (config('seo.canonical_url') ?: config('app.url')), '/').'/api/mcp';
    }

    public static function latestProtocolVersion(): string
    {
        return self::PROTOCOL_VERSIONS[0];
    }

    public static function negotiate(mixed $requested): string
    {
        return is_string($requested) && in_array($requested, self::PROTOCOL_VERSIONS, true)
            ? $requested
            : self::latestProtocolVersion();
    }

    public static function description(): string
    {
        return 'Free, read-only MCP server with live EU VAT rates, VAT calculations, rate changes and VIES VAT number validation for the 27 EU member states and five other European countries.';
    }

    public static function instructions(): string
    {
        return 'Use these tools for current EU VAT rates, VAT calculations, recorded and upcoming rate changes, and VIES VAT number checks. '
            .'Rates come from European Commission data and are updated every week; each result carries its last update time. '
            .'Countries can be given as an English name, an ISO code (Greece accepts EL, the United Kingdom UK) or a slug such as czech-republic. '
            .'Amounts are in the country\'s own currency. VAT treatment depends on the goods or services and the customer, so present results as reference data rather than tax advice.';
    }

    /**
     * @return list<array{name: string, title: string, description: string, inputSchema: array<string, mixed>, annotations: array<string, mixed>}>
     */
    public static function tools(): array
    {
        $country = [
            'type' => 'string',
            'description' => 'Country name (Germany), ISO code (DE; Greece also accepts EL) or slug (germany).',
        ];

        return [
            self::tool(
                'get_all_vat_rates',
                'Get all VAT rates',
                'Current standard, reduced, super-reduced and parking VAT rates for every EU member state and five other European countries, with currency and last update time.',
                [],
            ),
            self::tool(
                'get_country_vat_rate',
                'Get a country\'s VAT rates',
                'Current VAT rates, currency, VAT number format and last update time for one country.',
                ['country' => $country],
                ['country'],
            ),
            self::tool(
                'calculate_vat',
                'Calculate VAT',
                'Add VAT to a net amount or extract it from a gross amount at a country\'s standard, reduced, super-reduced or parking rate.',
                [
                    'amount' => ['type' => 'number', 'minimum' => 0, 'description' => 'Amount in the country\'s currency.'],
                    'country' => $country,
                    'mode' => ['type' => 'string', 'enum' => ['add', 'remove'], 'default' => 'add', 'description' => 'add: the amount is net and VAT is added. remove: the amount is gross and VAT is extracted.'],
                    'rate_type' => ['type' => 'string', 'enum' => ['standard', 'reduced', 'super_reduced', 'parking'], 'default' => 'standard', 'description' => 'Which rate to apply. reduced uses the lowest reduced rate.'],
                ],
                ['amount', 'country'],
            ),
            self::tool(
                'compare_vat_rates',
                'Compare VAT rates',
                'Side-by-side VAT rates for two to ten countries.',
                ['countries' => ['type' => 'array', 'items' => $country, 'minItems' => 2, 'maxItems' => 10, 'description' => 'Countries to compare.']],
                ['countries'],
            ),
            self::tool(
                'get_vat_rate_changes',
                'Get VAT rate changes',
                'Recorded and upcoming VAT rate changes with old and new rates and the date each takes effect, newest first, or soonest first for upcoming changes.',
                [
                    'country' => array_merge($country, ['description' => 'Optional. Only changes in this country, given as a name, ISO code or slug.']),
                    'upcoming_only' => ['type' => 'boolean', 'default' => false, 'description' => 'Only changes that take effect today or later.'],
                    'limit' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 50, 'default' => 20, 'description' => 'Maximum number of changes to return.'],
                ],
            ),
            self::tool(
                'validate_vat_number',
                'Validate an EU VAT number',
                'Check an EU VAT number against the European Commission\'s VIES service. Returns whether it is valid and, where the member state publishes them, the registered name and address.',
                [
                    'country_code' => ['type' => 'string', 'pattern' => '^[A-Za-z]{2}$', 'description' => 'Two-letter prefix of the VAT number, such as DE or FR. Greece uses EL.'],
                    'vat_number' => ['type' => 'string', 'description' => 'The VAT number, with or without the country prefix.'],
                ],
                ['country_code', 'vat_number'],
                openWorld: true,
            ),
        ];
    }

    /**
     * @return list<string>
     */
    public static function toolNames(): array
    {
        return array_column(self::tools(), 'name');
    }

    /**
     * @param  array<string, array<string, mixed>>  $properties
     * @param  list<string>  $required
     */
    private static function tool(
        string $name,
        string $title,
        string $description,
        array $properties,
        array $required = [],
        bool $openWorld = false,
    ): array {
        $schema = [
            'type' => 'object',
            'properties' => $properties === [] ? new stdClass : $properties,
            'additionalProperties' => false,
        ];

        if ($required !== []) {
            $schema['required'] = $required;
        }

        return [
            'name' => $name,
            'title' => $title,
            'description' => $description,
            'inputSchema' => $schema,
            'annotations' => [
                'title' => $title,
                'readOnlyHint' => true,
                'destructiveHint' => false,
                'idempotentHint' => true,
                'openWorldHint' => $openWorld,
            ],
        ];
    }
}
