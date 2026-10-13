<?php

namespace App\Support;

use App\Models\Country;
use Illuminate\Support\Facades\Cache;

final class SiteNavigation
{
    public static function isActive(string ...$patterns): bool
    {
        $candidates = [];

        foreach ($patterns as $pattern) {
            $candidates[] = $pattern;
            $candidates[] = 'locale.'.$pattern;
        }

        return request()->routeIs(...$candidates);
    }

    /**
     * @return list<array{label: string, description: string, url: string, icon: string, active: bool}>
     */
    public static function tools(): array
    {
        return [
            [
                'label' => __('ui.nav.vat_number_validator'),
                'description' => __('ui.nav.validator_desc'),
                'url' => locale_path('/vat-number-validator'),
                'icon' => 'shield-check',
                'active' => self::isActive('vies-validator*'),
            ],
            [
                'label' => __('ui.nav.vat_map'),
                'description' => __('ui.nav.map_desc'),
                'url' => locale_path('/vat-map'),
                'icon' => 'map',
                'active' => self::isActive('vat-map'),
            ],
            [
                'label' => __('ui.nav.updates'),
                'description' => __('ui.palette.updates_subtitle'),
                'url' => locale_path('/blog'),
                'icon' => 'news',
                'active' => self::isActive('blog.*'),
            ],
            [
                'label' => __('ui.nav.vat_widget'),
                'description' => __('ui.nav.widget_desc'),
                'url' => route('widget.embed'),
                'icon' => 'code',
                'active' => self::isActive('widget.*'),
            ],
            [
                'label' => __('ui.nav.api'),
                'description' => __('ui.nav.api_desc'),
                'url' => locale_path('/vat-validation-api'),
                'icon' => 'zap',
                'active' => self::isActive('vat-validation-api'),
            ],
            [
                'label' => __('ui.nav.dataset'),
                'description' => __('ui.nav.dataset_desc'),
                'url' => locale_path('/datasets/eu-vat-rates'),
                'icon' => 'database',
                'active' => self::isActive('vat-dataset'),
            ],
            [
                'label' => __('ui.sitemap.mcp_server'),
                'description' => __('ui.sitemap.mcp_server_desc'),
                'url' => locale_path('/mcp-server'),
                'icon' => 'server',
                'active' => self::isActive('mcp-server'),
            ],
            [
                'label' => __('ui.tools.top_calculations'),
                'description' => __('ui.tools.top_calculations_desc'),
                'url' => locale_path('/top-vat-calculations'),
                'icon' => 'trending-up',
                'active' => self::isActive('top-calculations*'),
            ],
        ];
    }

    /**
     * Items for the ⌘K command palette: every calculator country plus the main tools.
     *
     * @return list<array{type: string, title: string, subtitle: string, keywords: string, url: string, flag: ?string, meta: ?string}>
     */
    public static function paletteItems(): array
    {
        $locale = app()->getLocale();

        return Cache::remember("site_palette_items_v3_{$locale}", 600, function () {
            $countries = Country::calculatorAvailable()
                ->orderByDesc('is_eu_member')
                ->orderBy('name')
                ->get(['id', 'name', 'native_name', 'slug', 'iso_code', 'standard_rate', 'is_eu_member', 'vies_available']);

            $items = $countries
                ->map(fn (Country $country) => [
                    'type' => 'country',
                    'title' => $country->name,
                    'subtitle' => __('ui.palette.country_subtitle', ['rate' => Country::formatRate($country->standard_rate)]),
                    'keywords' => self::countryKeywords($country),
                    'url' => locale_path('/vat-calculator/'.$country->slug),
                    'flag' => strtolower((string) $country->iso_code),
                    'meta' => Country::formatRate($country->standard_rate).'%',
                ])
                ->all();

            // Each member state's validator and rate history, found by country name, code or what they are.
            $related = [];

            foreach ($countries->where('is_eu_member', true) as $country) {
                $names = self::countryKeywords($country);

                if ($country->vies_available) {
                    $related[] = [
                        'type' => 'country-page',
                        'title' => __('ui.vies_page.country_h1', ['country' => $country->name]),
                        'subtitle' => __('ui.nav.validator_desc'),
                        'keywords' => "{$names} vies vat number ".__('ui.nav.validator_short'),
                        'url' => locale_path('/vat-number-validator/'.$country->slug),
                        'flag' => strtolower((string) $country->iso_code),
                        'meta' => null,
                    ];
                }

                if ($country->hasVatHistory()) {
                    $related[] = [
                        'type' => 'country-page',
                        'title' => __('ui.calculator.full_rate_history', ['country' => $country->name]),
                        'subtitle' => __('ui.nav.history_desc'),
                        'keywords' => "{$names} history changes ".__('ui.nav.vat_history'),
                        'url' => locale_path('/vat-rates/'.$country->slug.'/history'),
                        'flag' => strtolower((string) $country->iso_code),
                        'meta' => null,
                    ];
                }
            }

            $pages = [
                ['title' => __('ui.nav.all_countries'), 'subtitle' => __('ui.palette.rates_subtitle'), 'url' => locale_path('/'), 'icon' => 'globe'],
                ['title' => __('ui.nav.vat_calculator'), 'subtitle' => __('ui.palette.calculator_subtitle'), 'url' => locale_path('/vat-calculator'), 'icon' => 'calculator'],
                ...array_map(fn (array $tool) => [
                    'title' => $tool['label'],
                    'subtitle' => $tool['description'],
                    'url' => $tool['url'],
                    'icon' => $tool['icon'],
                ], self::tools()),
                ['title' => __('ui.styleguide.nav_label'), 'subtitle' => __('ui.styleguide.nav_desc'), 'url' => locale_path('/styleguide'), 'icon' => 'grid', 'keywords' => 'styleguide design system tokens components storybook'],
                ['title' => __('ui.brand.nav_label'), 'subtitle' => __('ui.brand.nav_desc'), 'url' => locale_path('/brand'), 'icon' => 'flag', 'keywords' => 'brand logo colours voice press kit'],
                ['title' => __('ui.nav.vat_history'), 'subtitle' => __('ui.nav.history_desc'), 'url' => locale_path('/vat-changes'), 'icon' => 'history'],
                ['title' => __('ui.footer.vat_rates_api'), 'subtitle' => __('ui.nav.dataset_desc'), 'url' => '/api/v1/countries', 'icon' => 'code', 'keywords' => 'api json rest rates developers openapi'],
            ];

            $keywords = [
                locale_path('/datasets/eu-vat-rates') => 'json csv download open data',
                locale_path('/vat-validation-api') => 'api rest json vies batch developers',
                locale_path('/vat-changes') => 'history changes timeline',
                locale_path('/mcp-server') => 'mcp ai agents claude chatgpt',
                locale_path('/top-vat-calculations') => 'popular common amounts',
            ];

            foreach ($pages as $page) {
                $items[] = [
                    'type' => 'page',
                    'title' => $page['title'],
                    'subtitle' => $page['subtitle'],
                    'keywords' => $page['keywords'] ?? $keywords[$page['url']] ?? $page['icon'],
                    'url' => $page['url'],
                    'flag' => null,
                    'icon' => $page['icon'],
                    'meta' => null,
                ];
            }

            return [...$items, ...$related];
        });
    }

    /**
     * What a visitor may type for a country: its name in the page language (CLDR), its own name, code and slug.
     */
    private static function countryKeywords(Country $country): string
    {
        $iso = strtoupper((string) $country->iso_code) === 'EL' ? 'GR' : strtoupper((string) $country->iso_code);
        $local = class_exists(\Locale::class) ? (string) \Locale::getDisplayRegion('-'.$iso, app()->getLocale()) : '';

        return trim(implode(' ', array_unique(array_filter([$local, $country->native_name, $country->iso_code, $country->slug]))));
    }
}
