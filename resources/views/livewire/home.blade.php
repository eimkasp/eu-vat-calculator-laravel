@section('seo')
    @php($base = rtrim((string) config('seo.canonical_url'), '/'))
    <x-seo-meta :title="__('ui.home_page.title')" :description="__('ui.home_page.meta_desc')" type="website">
        <x-json-ld :data="[
            '@type' => 'WebSite',
            '@id' => $base.'/#website',
            'name' => __('ui.site_name'),
            'url' => $base.'/',
            'description' => __('ui.home_page.meta_desc'),
            'inLanguage' => app()->getLocale(),
            'publisher' => ['@id' => $base.'/#organization'],
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => ['@type' => 'EntryPoint', 'urlTemplate' => url(locale_path('/')).'?search={search_term_string}'],
                'query-input' => 'required name=search_term_string',
            ],
        ]" />
        <x-json-ld :data="[
            '@type' => 'Organization',
            '@id' => $base.'/#organization',
            'name' => 'EU VAT Info',
            'url' => $base.'/',
            'logo' => $base.'/icon-512x512.png',
            'description' => 'Current EU VAT rates, VAT calculators, VIES VAT number validation and open VAT data for the 27 EU member states.',
            'sameAs' => [
                'https://github.com/eimkasp/eu-vat.info',
                'https://chromewebstore.google.com/detail/eu-vat-calculator/fifmbbpgopnifnoginhmjjedjnabdkka',
            ],
        ]" />
        <x-json-ld :data="[
            '@type' => 'Dataset',
            '@id' => $base.'/datasets/eu-vat-rates#dataset',
            'name' => 'EU VAT Rates Dataset',
            'description' => 'Current standard, reduced, super-reduced and parking VAT rates for the 27 European Union member states, refreshed daily from European Commission data.',
            'url' => $base.'/datasets/eu-vat-rates',
            'license' => 'https://creativecommons.org/licenses/by/4.0/',
            'isAccessibleForFree' => true,
            'creator' => ['@type' => 'Organization', '@id' => $base.'/#organization', 'name' => 'EU VAT Info', 'url' => $base.'/'],
            'distribution' => [
                ['@type' => 'DataDownload', 'encodingFormat' => 'text/csv', 'contentUrl' => $base.'/datasets/eu-vat-rates.csv'],
                ['@type' => 'DataDownload', 'encodingFormat' => 'application/json', 'contentUrl' => $base.'/datasets/eu-vat-rates.json'],
            ],
            'temporalCoverage' => '2000/'.date('Y'),
            'spatialCoverage' => ['@type' => 'Place', 'name' => 'European Union'],
        ]" />
    </x-seo-meta>
@endsection

@push('head')
    <link rel="amphtml" href="{{ url('/amp') }}">
@endpush

<div>
    <section class="hero-canvas" aria-labelledby="home-heading">
        <x-hero-backdrop />
        <div class="app-container relative pb-12 pt-10 sm:pb-16 sm:pt-14">
            <div class="mx-auto mb-8 max-w-3xl text-center sm:mb-10">
                <p class="app-kicker">{{ __('ui.home_page.hero_badge', ['year' => date('Y'), 'count' => $stats['count'] ?? 27]) }}</p>
                <h1 id="home-heading" class="mt-4 text-4xl font-bold tracking-[-0.03em] text-white sm:text-5xl sm:leading-[1.08]">
                    {{ __('ui.home_page.heading') }}
                    {{ __('ui.home_page.heading_accent') }}
                </h1>
                <p class="mx-auto mt-4 max-w-[62ch] text-base leading-7 text-white/80 sm:text-lg">
                    {{ __('ui.home_page.subtitle') }}
                </p>
            </div>

            <livewire:hero-calculator surface="dark" />
        </div>
    </section>

    <section class="app-container py-12 sm:py-16">
        <div class="grid grid-cols-1 items-start gap-8 lg:grid-cols-[minmax(0,1fr)_22rem]">
            <x-country-rates-table :countries="$countries" :search="$search" :stats="$stats" />
            <x-home-sidebar />
        </div>
    </section>
</div>
