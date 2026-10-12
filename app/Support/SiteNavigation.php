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
                'label' => __('ui.nav.vat_history'),
                'description' => __('ui.nav.history_desc'),
                'url' => locale_path('/vat-changes'),
                'icon' => 'history',
                'active' => self::isActive('vat-changes*'),
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

        return Cache::remember("site_palette_items_v2_{$locale}", 600, function () {
            $items = Country::calculatorAvailable()
                ->orderByDesc('is_eu_member')
                ->orderBy('name')
                ->get(['name', 'native_name', 'slug', 'iso_code', 'standard_rate', 'is_eu_member'])
                ->map(fn (Country $country) => [
                    'type' => 'country',
                    'title' => $country->name,
                    'subtitle' => __('ui.palette.country_subtitle', ['rate' => Country::formatRate($country->standard_rate)]),
                    'keywords' => trim("{$country->native_name} {$country->iso_code} {$country->slug}"),
                    'url' => locale_path('/vat-calculator/'.$country->slug),
                    'flag' => strtolower((string) $country->iso_code),
                    'meta' => Country::formatRate($country->standard_rate).'%',
                ])
                ->all();

            $pages = [
                ['title' => __('ui.nav.all_countries'), 'subtitle' => __('ui.palette.rates_subtitle'), 'url' => locale_path('/'), 'icon' => 'globe'],
                ['title' => __('ui.nav.vat_calculator'), 'subtitle' => __('ui.palette.calculator_subtitle'), 'url' => locale_path('/vat-calculator'), 'icon' => 'calculator'],
                ...array_map(fn (array $tool) => [
                    'title' => $tool['label'],
                    'subtitle' => $tool['description'],
                    'url' => $tool['url'],
                    'icon' => $tool['icon'],
                ], self::tools()),
                ['title' => __('ui.sitemap.mcp_server'), 'subtitle' => __('ui.sitemap.mcp_server_desc'), 'url' => locale_path('/mcp-server'), 'icon' => 'server', 'keywords' => 'mcp ai agents claude'],
                ['title' => __('ui.styleguide.nav_label'), 'subtitle' => __('ui.styleguide.nav_desc'), 'url' => locale_path('/styleguide'), 'icon' => 'grid', 'keywords' => 'styleguide design system tokens components storybook'],
                ['title' => __('ui.brand.nav_label'), 'subtitle' => __('ui.brand.nav_desc'), 'url' => locale_path('/brand'), 'icon' => 'flag', 'keywords' => 'brand logo colours voice press kit'],
                ['title' => __('ui.nav.updates'), 'subtitle' => __('ui.palette.updates_subtitle'), 'url' => locale_path('/blog'), 'icon' => 'news'],
            ];

            foreach ($pages as $page) {
                $items[] = [
                    'type' => 'page',
                    'title' => $page['title'],
                    'subtitle' => $page['subtitle'],
                    'keywords' => $page['keywords'] ?? $page['icon'],
                    'url' => $page['url'],
                    'flag' => null,
                    'icon' => $page['icon'],
                    'meta' => null,
                ];
            }

            return $items;
        });
    }
}
