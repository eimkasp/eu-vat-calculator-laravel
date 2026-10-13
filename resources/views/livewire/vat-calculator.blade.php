@use('App\Models\Country')

@if($isCountryPage)
    @php
        $rateLabel = Country::formatRate($country->standard_rate);
        $calcMetaTitle = __('ui.calculator.meta_title_country', ['country' => $country->name, 'rate' => $rateLabel]);
        $calcMetaDesc = __('ui.calculator.meta_desc_country', ['country' => $country->name, 'rate' => $rateLabel]);
        $calcCanonical = app(\App\Support\Seo\SeoPolicy::class)->localizedUrl('/vat-calculator/'.$country->slug, app()->getLocale());
        $faqs = \App\Support\Vat\CountryFaq::for($country);
    @endphp
    @section('seo')
        <x-seo-meta :title="$calcMetaTitle" :description="$calcMetaDesc" :url="$calcCanonical">
            <x-json-ld :data="[
                '@type' => 'BreadcrumbList',
                'itemListElement' => [
                    ['@type' => 'ListItem', 'position' => 1, 'name' => __('ui.calculator.schema_breadcrumb_home'), 'item' => url(locale_path('/'))],
                    ['@type' => 'ListItem', 'position' => 2, 'name' => __('ui.calculator.schema_breadcrumb_calculator'), 'item' => url(locale_path('/vat-calculator'))],
                    ['@type' => 'ListItem', 'position' => 3, 'name' => $country->name, 'item' => $calcCanonical],
                ],
            ]" />
            <x-json-ld :data="[
                '@type' => 'WebPage',
                '@id' => $calcCanonical.'#webpage',
                'name' => __('ui.calculator.schema_page_name', ['country' => $country->name]),
                'description' => __('ui.calculator.schema_page_desc', ['country' => $country->name, 'rate' => $rateLabel]),
                'url' => $calcCanonical,
                'inLanguage' => app()->getLocale(),
                'dateModified' => $country->updated_at?->toIso8601String(),
                'isPartOf' => ['@type' => 'WebSite', '@id' => rtrim((string) config('seo.canonical_url'), '/').'/#website', 'name' => 'EU VAT Info', 'url' => rtrim((string) config('seo.canonical_url'), '/').'/'],
                'mainEntity' => [
                    '@type' => 'WebApplication',
                    '@id' => $calcCanonical.'#calculator',
                    'name' => __('ui.calculator.schema_app_name', ['country' => $country->name]),
                    'applicationCategory' => 'FinanceApplication',
                    'operatingSystem' => 'All',
                    'offers' => ['@type' => 'Offer', 'price' => '0', 'priceCurrency' => $country->currencyCode()],
                    'featureList' => 'VAT calculation, Add VAT, Remove VAT, Multiple rate types',
                    'about' => ['@type' => 'Country', 'name' => $country->name],
                ],
            ]" />
            <x-json-ld :data="[
                '@type' => 'FAQPage',
                'mainEntity' => collect($faqs)->map(fn (array $faq) => [
                    '@type' => 'Question',
                    'name' => $faq['q'],
                    'acceptedAnswer' => ['@type' => 'Answer', 'text' => $faq['a']],
                ])->all(),
            ]" />
        </x-seo-meta>
    @endsection
@else
    @section('seo')
        <x-seo-meta :title="__('ui.calculator.meta_title_generic')" :description="__('ui.calculator.meta_desc_generic')">
            <x-json-ld :data="[
                '@type' => 'WebApplication',
                'name' => __('ui.sitemap.calculator'),
                'description' => __('ui.calculator.meta_desc_generic'),
                'url' => url(locale_path('/vat-calculator')),
                'applicationCategory' => 'FinanceApplication',
                'operatingSystem' => 'All',
                'inLanguage' => app()->getLocale(),
                'isAccessibleForFree' => true,
                'offers' => ['@type' => 'Offer', 'price' => '0', 'priceCurrency' => 'EUR'],
                'publisher' => ['@type' => 'Organization', 'name' => 'EU VAT Info', 'url' => url('/')],
            ]" />
            <x-json-ld :data="[
                '@type' => 'BreadcrumbList',
                'itemListElement' => [
                    ['@type' => 'ListItem', 'position' => 1, 'name' => __('ui.calculator.schema_breadcrumb_home'), 'item' => url(locale_path('/'))],
                    ['@type' => 'ListItem', 'position' => 2, 'name' => __('ui.calculator.schema_breadcrumb_calculator'), 'item' => url(locale_path('/vat-calculator'))],
                ],
            ]" />
        </x-seo-meta>
    @endsection
@endif

@if($isCountryPage && app()->getLocale() === config('translation.default_language', 'en'))
    @push('head')
        <link rel="amphtml" href="{{ url('/amp/vat-calculator/'.$country->slug) }}">
    @endpush
@endif

<div>
    @if($isCountryPage)
        <section data-country-atmosphere class="hero-canvas">
            <x-hero-backdrop opacity="opacity-30" />
            <div class="app-container relative pb-10 pt-6 sm:pb-14 sm:pt-8">
                <x-calculator.country-header :country="$country" />

                <div id="calculator" role="region" class="mt-6 scroll-mt-24 sm:mt-8" aria-label="{{ __('ui.calculator.country_heading', ['country' => $country->name]) }}">
                    <livewire:hero-calculator :key="'hero-calc-'.$country->id" :initial-country="$country->slug" :initial-amount="$prefill['amount']" :initial-rate="$prefill['rate']" :initial-mode="$prefill['mode']" surface="country-image" />
                </div>
            </div>
        </section>

        <div class="app-container py-10 sm:py-14">
            <div class="grid grid-cols-1 items-start gap-8 xl:grid-cols-[minmax(0,1fr)_20rem]">
                <div class="min-w-0 space-y-8">
                    <x-calculator.country-reference :country="$country" />
                    <x-calculator.common-amounts :country="$country" />

                    @if($country->is_eu_member)
                        @php
                            $comparisonPairs = app(\App\Services\Seo\InternalLinkService::class)->approvedPairsFor($country);
                            $categoryRules = app(\App\Services\Seo\VatCategorySeoService::class)->rulesForCountry($country->slug);
                            $comparisonCountries = Country::query()
                                ->whereIn('slug', $comparisonPairs->flatten()->reject(fn ($slug) => $slug === $country->slug)->all())
                                ->where('is_eu_member', true)
                                ->get()
                                ->keyBy('slug');
                        @endphp
                        <section aria-labelledby="country-guide-title" class="app-surface p-5 sm:p-6">
                            <p class="app-eyebrow">{{ __('ui.country_page.eu_guidance') }}</p>
                            <h2 id="country-guide-title" class="mt-1 text-xl font-bold text-ink">{{ __('ui.country_page.vat_guide_heading', ['country' => $country->name]) }}</h2>
                            <p class="mt-3 max-w-[72ch] text-sm leading-6 text-ink-muted">
                                {{ __('ui.country_page.vat_registration_text', ['country' => $country->name, 'rate' => Country::formatRate($country->standard_rate)]) }}
                            </p>

                            <div class="mt-5 flex flex-wrap gap-2.5">
                                @if($country->vies_available)
                                    <a href="{{ locale_path('/vat-number-validator/'.$country->slug) }}" class="app-button-secondary">
                                        <x-ui.icon name="shield-check" class="size-4" />
                                        {{ __('ui.country_page.validate_vat_cta', ['country' => $country->name]) }}
                                    </a>
                                @endif
                                @if($country->hasVatHistory())
                                    <a href="{{ locale_path('/vat-rates/'.$country->slug.'/history') }}" class="app-button-secondary">
                                        <x-ui.icon name="history" class="size-4" />
                                        {{ __('ui.country_page.rate_history_link') }}
                                    </a>
                                @endif
                                <a href="{{ locale_path('/vat-map') }}" class="app-button-secondary">
                                    <x-ui.icon name="map" class="size-4" />
                                    {{ __('ui.country_page.vat_map_link') }}
                                </a>
                            </div>

                            @if($comparisonPairs->isNotEmpty() || $categoryRules->isNotEmpty())
                                <div class="mt-5 flex flex-wrap gap-2 border-t border-line pt-5">
                                    @foreach($comparisonPairs as $comparisonPair)
                                        @php($other = $comparisonCountries->get($comparisonPair[0] === $country->slug ? $comparisonPair[1] : $comparisonPair[0]))
                                        @if($other)
                                            <a class="app-chip" href="{{ locale_path('/compare/'.implode('-vs-', $comparisonPair).'-vat') }}">
                                                <x-ui.flag :iso="$other->iso_code" size="xs" />
                                                {{ __('ui.country_page.compare_with', ['country' => $other->name]) }}
                                            </a>
                                        @endif
                                    @endforeach
                                    @foreach($categoryRules as $categoryRule)
                                        <a class="app-chip" href="{{ locale_path('/vat-rates/'.$country->slug.'/categories/'.$categoryRule->category_slug) }}">
                                            {{ __('ui.country_page.category_rate', ['category' => $categoryRule->category_name]) }}
                                        </a>
                                    @endforeach
                                </div>
                            @endif
                        </section>
                    @endif

                    <x-faq-list :faqs="$faqs" :title="__('ui.calculator.faq_heading', ['country' => $country->name])" id="country-faq" />
                </div>

                <aside class="space-y-6">
                    <section aria-labelledby="related-calculators-title" class="app-surface p-5">
                        <h2 id="related-calculators-title" class="text-base font-bold text-ink">{{ __('ui.country_page.related') }}</h2>
                        <div class="mt-3">
                            <x-related-countries :country="$country" />
                        </div>
                    </section>
                    <x-country-calculator-list />
                </aside>
            </div>
        </div>
    @else
        <section class="hero-canvas">
            <x-hero-backdrop />
            <div class="app-container relative pb-12 pt-6 sm:pb-16 sm:pt-8">
                <x-site-breadcrumbs variant="dark" :items="[__('ui.calculator.breadcrumb_label') => '']" />
                <div class="mx-auto mb-8 mt-6 max-w-3xl text-center">
                    <h1 class="text-3xl font-bold tracking-[-0.03em] text-white sm:text-5xl sm:leading-[1.08]">{{ __('ui.calculator.european_heading') }}</h1>
                    <p class="mx-auto mt-4 max-w-2xl text-base leading-7 text-white/80 sm:text-lg">{{ __('ui.calculator.generic_subtitle') }}</p>
                </div>
                <livewire:hero-calculator :key="'hero-calc-default'" :initial-amount="$prefill['amount']" :initial-rate="$prefill['rate']" :initial-mode="$prefill['mode']" surface="dark" />
            </div>
        </section>

        <div class="app-container py-10 sm:py-14">
            <div class="grid grid-cols-1 items-start gap-8 lg:grid-cols-[minmax(0,1fr)_20rem]">
                <section class="app-surface p-4 sm:p-6">
                    <x-europe-map :countries="\App\Models\Country::forMap()" />
                </section>
                <aside class="space-y-6">
                    <x-country-calculator-list />
                </aside>
            </div>
        </div>
    @endif
</div>
