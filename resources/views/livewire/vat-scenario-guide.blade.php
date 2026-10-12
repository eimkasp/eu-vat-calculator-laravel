@php
    $baseUrl = app(\App\Support\Seo\SeoPolicy::class)->canonicalHost();
    $canonical = $baseUrl.locale_path('/vat-guides/'.$scenario);
    $sourceTitle = fn (string $url) => match (Str::afterLast(rtrim((string) parse_url($url, PHP_URL_PATH), '/'), '/')) {
        'place-taxation_en' => 'Place of taxation',
        'persons-liable-vat_en' => 'Persons liable for VAT',
        'vat-special-schemes-oss_en' => 'One Stop Shop (OSS)',
        default => 'VAT guidance',
    };
@endphp

@section('seo')
    <x-seo-meta :title="$guide['title'].' | EU VAT Info'" :description="$guide['summary']" :url="$canonical" type="article">
        <script type="application/ld+json">{!! json_encode([
            '@@context' => 'https://schema.org',
            '@graph' => [
                ['@type' => 'Article', '@id' => $canonical.'#article', 'headline' => $guide['title'], 'description' => $guide['summary'], 'mainEntityOfPage' => $canonical, 'author' => ['@type' => 'Organization', 'name' => 'EU VAT Info'], 'citation' => $guide['sources']],
            ],
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
    </x-seo-meta>
@endsection

<article>
    <x-page-header :title="$guide['title']" :description="$guide['summary']" eyebrow="EU VAT scenario guide" :breadcrumbs="['VAT tools' => locale_path('/tools'), $guide['title'] => '']" />

    <div class="app-container space-y-8 py-8 sm:py-10">
        <div class="grid grid-cols-1 items-start gap-8 lg:grid-cols-[minmax(0,1fr)_20rem]">
            <div class="app-surface min-w-0 divide-y divide-line">
                <section class="p-5 sm:p-6" aria-labelledby="guide-rule">
                    <h2 id="guide-rule" class="text-lg font-bold text-ink">General rule</h2>
                    <p class="mt-2 max-w-[68ch] text-base leading-7 text-ink-muted">{{ $guide['rule'] }}</p>
                </section>
                <section class="p-5 sm:p-6" aria-labelledby="guide-exceptions">
                    <h2 id="guide-exceptions" class="text-lg font-bold text-ink">Important exceptions</h2>
                    <p class="mt-2 max-w-[68ch] text-base leading-7 text-ink-muted">{{ $guide['exceptions'] }}</p>
                </section>
                <section class="p-5 sm:p-6" aria-labelledby="guide-references">
                    <h2 id="guide-references" class="text-lg font-bold text-ink">Legal references</h2>
                    <ul class="mt-3 flex flex-wrap gap-2">
                        @foreach($guide['legal_refs'] as $reference)
                            <li class="app-badge bg-surface-muted text-ink">{{ $reference }}</li>
                        @endforeach
                    </ul>
                    <p class="app-note mt-5">
                        <x-ui.icon name="info" class="mt-1 size-4 text-action" />
                        <span>This guide summarizes the general EU framework. Classification, establishment, customer evidence, national implementation and special schemes can change the result. Confirm material transactions with the relevant tax authority or adviser.</span>
                    </p>
                </section>
            </div>

            <aside class="app-surface p-5 lg:sticky lg:top-24">
                <h2 class="text-base font-bold text-ink">Official sources</h2>
                <ul class="-mx-2 mt-2 space-y-0.5 text-sm">
                    @foreach($guide['sources'] as $source)
                        <li>
                            <a class="group flex items-start justify-between gap-3 rounded-control p-2 transition-colors hover:bg-surface-subtle" href="{{ $source }}" rel="noopener noreferrer">
                                <span>
                                    <span class="block font-semibold text-action group-hover:underline">{{ $sourceTitle($source) }}</span>
                                    <span class="block text-xs text-ink-muted">European Commission</span>
                                </span>
                                <x-ui.icon name="arrow-up-right" class="mt-0.5 size-4 text-ink-quiet" />
                            </a>
                        </li>
                    @endforeach
                </ul>
                <a class="app-button-primary mt-5 w-full" href="{{ locale_path('/tools') }}">Open VAT tools</a>
            </aside>
        </div>

        <section aria-labelledby="related-scenarios">
            <h2 id="related-scenarios" class="text-xl font-bold text-ink">Related VAT scenarios</h2>
            <ul class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                @foreach(config('vat-scenarios') as $relatedSlug => $relatedGuide)
                    @continue($relatedSlug === $scenario)
                    <li>
                        <a class="app-surface pressable flex h-full items-center justify-between gap-3 p-4 text-sm font-semibold text-ink hover:border-line-strong hover:text-action" href="{{ locale_path('/vat-guides/'.$relatedSlug) }}">
                            {{ $relatedGuide['title'] }}
                            <x-ui.icon name="chevron-right" class="size-4 text-ink-quiet" />
                        </a>
                    </li>
                @endforeach
            </ul>
        </section>
    </div>
</article>
