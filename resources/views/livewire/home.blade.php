@section('seo')
    @php($base = rtrim((string) config('seo.canonical_url'), '/'))
    <x-seo-meta :title="__('ui.home_page.title')" :description="__('ui.home_page.meta_desc')" type="website">
        <x-json-ld :data="[
            '@type' => 'WebSite',
            '@id' => $base.'/#website',
            'name' => 'EU VAT Info',
            'alternateName' => array_values(array_unique(['vat.businesspress.io', __('ui.site_name')])),
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
        <x-json-ld :data="\App\Support\Seo\DatasetSchema::rates()" />
    </x-seo-meta>
@endsection

@if(app()->getLocale() === config('translation.default_language', 'en'))
    @push('head')
        <link rel="amphtml" href="{{ url('/amp') }}">
    @endpush
@endif

<div>
    <section class="hero-field" aria-labelledby="home-heading">
        <x-hero-backdrop />
        <div class="app-container relative pb-10 pt-8 sm:pb-14 sm:pt-12">
            <div class="mb-7 max-w-2xl sm:mb-9">
                <h1 id="home-heading" class="text-4xl font-semibold tracking-[-0.03em] text-ink sm:text-5xl sm:leading-[1.04]">
                    {{ __('ui.home_page.heading') }}
                    {{ __('ui.home_page.heading_accent') }}
                </h1>
                <p class="mt-4 max-w-[54ch] text-base leading-7 text-ink-muted sm:text-lg">
                    {{ __('ui.home_page.subtitle') }}
                </p>
            </div>

            <livewire:hero-calculator surface="field" />
        </div>
    </section>

    <section class="app-container py-12 sm:py-16">
        <div class="grid grid-cols-1 items-start gap-8 lg:grid-cols-[minmax(0,1fr)_22rem]">
            <x-country-rates-table :countries="$countries" :search="$search" :stats="$stats" />
            <x-home-sidebar />
        </div>
    </section>
</div>
