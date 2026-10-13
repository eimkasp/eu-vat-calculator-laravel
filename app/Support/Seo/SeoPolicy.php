<?php

namespace App\Support\Seo;

use Illuminate\Support\Facades\Route;

class SeoPolicy
{
    /**
     * Pages whose body text is English in every language. Their translated URLs canonicalize to the English page
     * and carry no hreflang, so they are not indexed as duplicates. Remove a route once its page is translated.
     */
    public const ENGLISH_BODY_ROUTES = [
        'blog.index',
        'brand',
        'blog.show',
        'changelog',
        'chrome-extension',
        'donate',
        'privacy',
        'styleguide',
        'vat-dataset',
        'vat-validation-api',
        'vat-changes.event',
        'vat-changes.upcoming',
        'vat-changes.year',
        'vat-rates.categories',
        'vat-rates.category',
        'vat-rates.country-category',
        'vat-rates.country-history',
        'vat-scenario-guide',
    ];

    /**
     * Open Graph locale (language_TERRITORY) for each supported language.
     */
    public const OG_LOCALES = [
        'bg' => 'bg_BG', 'cs' => 'cs_CZ', 'da' => 'da_DK', 'de' => 'de_DE', 'el' => 'el_GR', 'en' => 'en_GB',
        'es' => 'es_ES', 'et' => 'et_EE', 'fi' => 'fi_FI', 'fr' => 'fr_FR', 'ga' => 'ga_IE', 'hr' => 'hr_HR',
        'hu' => 'hu_HU', 'it' => 'it_IT', 'lt' => 'lt_LT', 'lv' => 'lv_LV', 'mt' => 'mt_MT', 'nl' => 'nl_NL',
        'pl' => 'pl_PL', 'pt' => 'pt_PT', 'ro' => 'ro_RO', 'sk' => 'sk_SK', 'sl' => 'sl_SI', 'sv' => 'sv_SE',
    ];

    public function ogLocale(?string $locale = null): string
    {
        $locale ??= app()->getLocale();

        return self::OG_LOCALES[$locale] ?? str_replace('-', '_', $locale);
    }

    public function canonicalHost(): string
    {
        return rtrim((string) config('seo.canonical_url', 'https://vat.businesspress.io'), '/');
    }

    /**
     * The locales search engines may index: the configured list, or every supported language when none is set.
     *
     * @return array<int, string>
     */
    public function indexableLocales(): array
    {
        $configured = array_values(array_unique(array_filter((array) config('seo.indexable_locales'))));

        return $configured !== [] ? $configured : array_keys(config('translation.supported_languages', ['en' => []]));
    }

    public function isLocaleIndexable(string $locale): bool
    {
        return in_array($locale, $this->indexableLocales(), true);
    }

    public function robotsForCurrentLocale(): string
    {
        return $this->isLocaleIndexable(app()->getLocale())
            ? 'index, follow'
            : 'noindex, follow';
    }

    public function isTranslatedRoute(?string $route): bool
    {
        return ! in_array(preg_replace('/^locale\./', '', (string) $route), self::ENGLISH_BODY_ROUTES, true);
    }

    public function hasTranslatedBody(): bool
    {
        return $this->isTranslatedRoute(request()->route()?->getName());
    }

    public function shouldEmitHreflang(): bool
    {
        if (! $this->isLocaleIndexable(app()->getLocale()) || ! $this->hasTranslatedBody() || ! $this->hasLocalizedRoute()) {
            return false;
        }

        if (str_contains((string) request()->attributes->get('seo.robots'), 'noindex')) {
            return false;
        }

        if (request()->routeIs('shared-calculation', 'locale.shared-calculation')) {
            return CalculationIndexing::isTopCalculation(
                (string) request()->route('country'),
                (float) request()->route('amount'),
                (float) request()->route('rate'),
            );
        }

        return true;
    }

    /**
     * Whether the current page exists in every language: a /{locale} route, or an English route with one.
     */
    public function hasLocalizedRoute(): bool
    {
        $name = (string) request()->route()?->getName();

        return $name !== '' && (str_starts_with($name, 'locale.') || Route::has('locale.'.$name));
    }

    /**
     * The canonical URL for a page: its own URL, or the English one when the page body is not translated.
     */
    public function canonicalPageUrl(string $url): string
    {
        $canonical = $this->canonicalizeLocalUrl($url);
        $host = $this->canonicalHost();

        if ($this->hasTranslatedBody() || ! str_starts_with($canonical, $host)) {
            return $canonical;
        }

        $default = config('translation.default_language', 'en');
        $others = array_diff(array_keys(config('translation.supported_languages', [])), [$default]);
        $path = preg_replace('#^/('.implode('|', array_map('preg_quote', $others)).')(?=[/?]|$)#', '', substr($canonical, strlen($host)));

        return $host.(str_starts_with($path, '/') ? $path : '/'.$path);
    }

    public function localizedUrl(string $path, string $locale): string
    {
        $default = config('translation.default_language', 'en');
        $path = '/'.ltrim($path, '/');
        $path = $path === '/' ? '' : $path;

        if ($locale === $default) {
            return $this->canonicalHost().($path ?: '/');
        }

        return $this->canonicalHost().'/'.$locale.$path;
    }

    public function canonicalizeLocalUrl(string $url): string
    {
        $parts = parse_url($url);
        $host = $parts['host'] ?? null;
        $canonicalHost = parse_url($this->canonicalHost(), PHP_URL_HOST);
        $localHosts = array_merge(
            [$canonicalHost, request()->getHost()],
            config('seo.legacy_hosts', [])
        );

        if ($host && ! in_array($host, array_filter($localHosts), true)) {
            return $url;
        }

        $path = $parts['path'] ?? '/';
        $query = isset($parts['query']) ? '?'.$parts['query'] : '';

        return $this->canonicalHost().($path === '' ? '/' : $path).$query;
    }
}
