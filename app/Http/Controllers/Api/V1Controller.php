<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Services\ViesValidationService;
use App\Support\Vat\VatCalculation;
use App\Support\Vat\VatMode;
use App\Support\Vat\VatNumberFormat;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class V1Controller extends Controller
{
    /**
     * GET /api/v1 — API index with links to all v1 endpoints.
     */
    public function index(): JsonResponse
    {
        $baseUrl = rtrim(config('app.url'), '/');

        return response()->json([
            'name' => 'EU VAT Info API',
            'version' => 'v1',
            'description' => 'Free EU VAT rates, calculator, and VIES validation for all 27 member states.',
            'documentation' => $baseUrl.'/api/v1/openapi.json',
            'endpoints' => [
                'countries' => $baseUrl.'/api/v1/countries',
                'country' => $baseUrl.'/api/v1/countries/{slug}',
                'calculate' => $baseUrl.'/api/v1/calculate',
                'validate' => $baseUrl.'/api/v1/validate',
            ],
        ]);
    }

    /**
     * GET /api/v1/countries — All EU countries with VAT rates.
     */
    public function countries(): JsonResponse
    {
        $countries = Cache::remember('api_v1_countries', 600, function () {
            return Country::orderBy('name')
                ->get()
                ->map(fn (Country $c) => $this->formatCountry($c))
                ->values();
        });

        return response()->json(['data' => $countries]);
    }

    /**
     * GET /api/v1/countries/{slug} — Single country by slug or ISO code.
     */
    public function country(string $slug): JsonResponse
    {
        $country = Cache::remember("api_v1_country_{$slug}", 600, function () use ($slug) {
            return Country::where('slug', $slug)
                ->orWhere('iso_code', strtoupper($slug))
                ->firstOrFail();
        });

        return response()->json(['data' => $this->formatCountry($country)]);
    }

    /**
     * GET /api/v1/calculate — Calculate VAT for an amount and country.
     */
    public function calculate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0|max:999999999',
            'country' => 'required|string|max:50',
            'rate_type' => 'sometimes|string|in:standard,reduced,super_reduced,parking',
            'mode' => 'sometimes|string|in:add,remove',
        ]);

        $country = Country::where('slug', $validated['country'])
            ->orWhere('iso_code', strtoupper($validated['country']))
            ->orWhere('name', $validated['country'])
            ->first();

        if (! $country) {
            return response()->json(['error' => 'Country not found.'], 404);
        }

        $rateType = $validated['rate_type'] ?? 'standard';
        $mode = $validated['mode'] ?? 'add';
        $amount = (float) $validated['amount'];

        $rate = $country->rateForType($rateType);

        if ($rate === null) {
            return response()->json([
                'error' => "Rate type '{$rateType}' is not available for {$country->name}.",
            ], 422);
        }

        $calculation = VatCalculation::make($amount, $rate, $mode === 'remove' ? VatMode::Include : VatMode::Exclude);
        $net = $calculation->net;
        $vat = $calculation->vat;
        $gross = $calculation->gross;

        return response()->json([
            'data' => [
                'country' => $country->name,
                'iso_code' => $country->iso_code,
                'rate_type' => $rateType,
                'rate' => $rate,
                'mode' => $mode,
                'amount' => $amount,
                'net' => $net,
                'vat' => $vat,
                'gross' => $gross,
                'currency' => $country->currencyCode(),
            ],
        ]);
    }

    /**
     * POST /api/v1/validate — Validate a VAT number via VIES.
     */
    public function validateVat(Request $request, ViesValidationService $viesService): JsonResponse
    {
        $validated = $request->validate([
            'country_code' => 'required|string|size:2',
            'vat_number' => 'required|string|min:5|max:50',
        ]);

        $result = $viesService->validate(
            $validated['country_code'],
            $validated['vat_number'],
        );

        return response()->json([
            'data' => $result,
        ]);
    }

    /**
     * GET /api/v1/openapi.json — OpenAPI 3.1 specification.
     */
    public function openapi(): JsonResponse
    {
        $baseUrl = rtrim(config('app.url'), '/');

        $spec = [
            'openapi' => '3.1.0',
            'info' => [
                'title' => 'EU VAT Info API',
                'version' => '1.0.0',
                'description' => 'Free, open EU VAT rate data, calculator, and VIES validation for all 27 EU member states. No authentication required.',
                'contact' => ['url' => $baseUrl],
                'license' => ['name' => 'MIT', 'url' => 'https://opensource.org/licenses/MIT'],
            ],
            'servers' => [
                ['url' => $baseUrl.'/api/v1', 'description' => 'Production'],
            ],
            'paths' => [
                '/countries' => [
                    'get' => [
                        'operationId' => 'listCountries',
                        'summary' => 'List all EU countries with VAT rates',
                        'tags' => ['Countries'],
                        'responses' => [
                            '200' => [
                                'description' => 'Array of EU countries with current VAT rates',
                                'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/CountryListResponse']]],
                            ],
                        ],
                    ],
                ],
                '/countries/{slug}' => [
                    'get' => [
                        'operationId' => 'getCountry',
                        'summary' => 'Get a single country by slug or ISO code',
                        'tags' => ['Countries'],
                        'parameters' => [
                            ['name' => 'slug', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'string'], 'description' => 'Country slug (germany), ISO alpha-2 (DE), or code'],
                        ],
                        'responses' => [
                            '200' => [
                                'description' => 'Country with VAT rates',
                                'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/CountryResponse']]],
                            ],
                            '404' => ['description' => 'Country not found'],
                        ],
                    ],
                ],
                '/calculate' => [
                    'get' => [
                        'operationId' => 'calculateVat',
                        'summary' => 'Calculate VAT for an amount and country',
                        'tags' => ['Calculator'],
                        'parameters' => [
                            ['name' => 'amount', 'in' => 'query', 'required' => true, 'schema' => ['type' => 'number'], 'description' => 'Monetary amount'],
                            ['name' => 'country', 'in' => 'query', 'required' => true, 'schema' => ['type' => 'string'], 'description' => 'Country slug, ISO code, or name'],
                            ['name' => 'rate_type', 'in' => 'query', 'required' => false, 'schema' => ['type' => 'string', 'enum' => ['standard', 'reduced', 'super_reduced', 'parking'], 'default' => 'standard']],
                            ['name' => 'mode', 'in' => 'query', 'required' => false, 'schema' => ['type' => 'string', 'enum' => ['add', 'remove'], 'default' => 'add'], 'description' => 'add = net→gross, remove = gross→net'],
                        ],
                        'responses' => [
                            '200' => [
                                'description' => 'Calculation result',
                                'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/CalculationResponse']]],
                            ],
                            '404' => ['description' => 'Country not found'],
                            '422' => ['description' => 'Rate type unavailable for country'],
                        ],
                    ],
                ],
                '/validate' => [
                    'post' => [
                        'operationId' => 'validateVatNumber',
                        'summary' => 'Validate an EU VAT number via VIES',
                        'tags' => ['Validation'],
                        'requestBody' => [
                            'required' => true,
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'required' => ['country_code', 'vat_number'],
                                        'properties' => [
                                            'country_code' => ['type' => 'string', 'minLength' => 2, 'maxLength' => 2, 'description' => 'ISO country code (use EL for Greece)'],
                                            'vat_number' => ['type' => 'string', 'minLength' => 5, 'maxLength' => 50, 'description' => 'VAT number without country prefix'],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        'responses' => [
                            '200' => [
                                'description' => 'Validation result with company info if valid',
                                'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/ValidationResponse']]],
                            ],
                            '422' => ['description' => 'Validation error'],
                        ],
                    ],
                ],
            ],
            'components' => [
                'schemas' => [
                    'Country' => [
                        'type' => 'object',
                        'properties' => [
                            'name' => ['type' => 'string', 'example' => 'Germany'],
                            'iso_code' => ['type' => 'string', 'example' => 'DE'],
                            'slug' => ['type' => 'string', 'example' => 'germany'],
                            'currency' => ['type' => 'string', 'example' => 'EUR'],
                            'rates' => [
                                'type' => 'object',
                                'properties' => [
                                    'standard' => ['type' => 'number', 'example' => 19],
                                    'reduced' => ['type' => ['number', 'null'], 'example' => 7, 'description' => 'The lowest reduced rate'],
                                    'reduced_rates' => ['type' => 'array', 'items' => ['type' => 'number'], 'example' => [7], 'description' => 'Every reduced rate, lowest first'],
                                    'super_reduced' => ['type' => ['number', 'null']],
                                    'parking' => ['type' => ['number', 'null']],
                                ],
                            ],
                            'vat_number' => [
                                'type' => ['object', 'null'],
                                'description' => 'The shape of the country\'s VAT numbers in the Commission\'s notation (9 a digit, L a letter, X a letter or a digit); null outside the EU VAT system. VIES decides whether a number exists.',
                                'properties' => [
                                    'prefix' => ['type' => 'string', 'example' => 'DE'],
                                    'format' => ['type' => 'string', 'example' => 'DE999999999'],
                                    'masks' => ['type' => 'array', 'items' => ['type' => 'string'], 'example' => ['DE999999999']],
                                    'pattern' => ['type' => 'string', 'example' => '^DE\\d{9}$', 'description' => 'Regular expression for a number written without spaces or punctuation'],
                                ],
                            ],
                            'last_updated' => ['type' => 'string', 'format' => 'date-time'],
                        ],
                    ],
                    'CountryListResponse' => [
                        'type' => 'object',
                        'properties' => [
                            'data' => ['type' => 'array', 'items' => ['$ref' => '#/components/schemas/Country']],
                        ],
                    ],
                    'CountryResponse' => [
                        'type' => 'object',
                        'properties' => [
                            'data' => ['$ref' => '#/components/schemas/Country'],
                        ],
                    ],
                    'CalculationResponse' => [
                        'type' => 'object',
                        'properties' => [
                            'data' => [
                                'type' => 'object',
                                'properties' => [
                                    'country' => ['type' => 'string'],
                                    'iso_code' => ['type' => 'string'],
                                    'rate_type' => ['type' => 'string'],
                                    'rate' => ['type' => 'number'],
                                    'mode' => ['type' => 'string'],
                                    'amount' => ['type' => 'number'],
                                    'net' => ['type' => 'number'],
                                    'vat' => ['type' => 'number'],
                                    'gross' => ['type' => 'number'],
                                    'currency' => ['type' => 'string'],
                                ],
                            ],
                        ],
                    ],
                    'ValidationResponse' => [
                        'type' => 'object',
                        'properties' => [
                            'data' => [
                                'type' => 'object',
                                'properties' => [
                                    'valid' => ['type' => 'boolean'],
                                    'country_code' => ['type' => 'string'],
                                    'vat_number' => ['type' => 'string'],
                                    'company_name' => ['type' => ['string', 'null']],
                                    'company_address' => ['type' => ['string', 'null']],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        return response()->json($spec)
            ->header('Cache-Control', 'public, max-age=3600');
    }

    private function formatCountry(Country $c): array
    {
        return [
            'name' => $c->name,
            'iso_code' => $c->iso_code,
            'code' => $c->iso_code,
            'slug' => $c->slug,
            'currency' => $c->currencyCode(),
            'rates' => $c->apiRates(),
            'vat_number' => VatNumberFormat::forCountry($c),
            'last_updated' => $c->updated_at?->toIso8601String(),
        ];
    }
}
