@php
    $baseUrl = app(\App\Support\Seo\SeoPolicy::class)->canonicalHost();
    $canonical = $baseUrl.locale_path('/vat-rates/categories');
@endphp

@section('seo')
    <x-seo-meta
        title="EU VAT Rates by Category — Verified Country Rules"
        description="Browse verified VAT category rules across EU countries, including rates, effective dates, coverage and source links."
        :url="$canonical">
        <script type="application/ld+json">{!! json_encode([
            '@@context' => 'https://schema.org',
            '@type' => 'CollectionPage',
            '@id' => $canonical.'#webpage',
            'name' => 'EU VAT rates by category',
            'url' => $canonical,
            'mainEntity' => [
                '@type' => 'ItemList',
                'itemListElement' => $categories->values()->map(fn (array $category, int $index) => [
                    '@type' => 'ListItem',
                    'position' => $index + 1,
                    'name' => $category['name'],
                    'url' => $baseUrl.'/vat-rates/categories/'.$category['slug'],
                ])->all(),
            ],
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
    </x-seo-meta>
@endsection

<div>
    <x-page-header
        title="EU VAT rates by category"
        description="Compare published category-specific VAT rules only where country coverage and source verification meet our indexing standard."
        eyebrow="Verified category rules"
        :breadcrumbs="['VAT rate categories' => '']"
    />

    <div class="app-container space-y-6 py-8 sm:py-10">
        <ul class="app-surface divide-y divide-line overflow-hidden">
            @foreach($categories as $category)
                <li>
                    <a href="{{ locale_path('/vat-rates/categories/'.$category['slug']) }}" class="group flex items-center gap-4 px-5 py-4 transition-colors hover:bg-surface-subtle sm:px-6">
                        <span class="min-w-0 flex-1">
                            <span class="block font-semibold text-ink group-hover:text-action">{{ $category['name'] }}</span>
                            <span class="tabular mt-0.5 flex flex-wrap gap-x-3 text-sm text-ink-muted">
                                <span>{{ $category['country_count'] }} EU countries</span>
                                <span>{{ \App\Models\Country::formatRate($category['minimum_rate']) }}–{{ \App\Models\Country::formatRate($category['maximum_rate']) }}%</span>
                            </span>
                        </span>
                        <span class="hidden text-sm font-semibold text-action sm:inline">Compare rates</span>
                        <x-ui.icon name="chevron-right" class="size-4 text-ink-quiet group-hover:text-action" />
                    </a>
                </li>
            @endforeach
        </ul>

        <p class="app-note">
            <x-ui.icon name="info" class="mt-1 size-4 text-action" />
            <span>Category treatment can depend on product definitions, transaction details and national law. Every published rule links to its recorded source and verification date.</span>
        </p>
    </div>
</div>
