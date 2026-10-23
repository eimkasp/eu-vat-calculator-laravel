@use('App\Models\Country')
@use('App\Support\Seo\SeoPolicy')

@php
    $canonical = app(SeoPolicy::class)->canonicalHost().locale_path('/compare');
    $updated = collect($comparisons)->flatMap(fn (array $comparison) => [$comparison['left']->updated_at, $comparison['right']->updated_at])->filter()->max();
@endphp

@section('seo')
    <x-seo-meta :title="__('ui.compare_index.meta_title')" :description="__('ui.compare_index.meta_description')" :url="$canonical">
        <x-json-ld :data="[
            '@type' => 'CollectionPage',
            '@id' => $canonical.'#webpage',
            'name' => __('ui.compare_index.title'),
            'url' => $canonical,
            'inLanguage' => app()->getLocale(),
            'mainEntity' => [
                '@type' => 'ItemList',
                'itemListElement' => collect($comparisons)->values()->map(fn (array $comparison, int $index) => [
                    '@type' => 'ListItem',
                    'position' => $index + 1,
                    'name' => $comparison['label'],
                    'url' => app(SeoPolicy::class)->canonicalHost().$comparison['url'],
                ])->all(),
            ],
        ]" />
    </x-seo-meta>
@endsection

<div>
    <x-page-header :title="__('ui.compare_index.title')" :description="__('ui.compare_index.intro')" :breadcrumbs="[__('ui.nav.compare') => '']" />

    <div class="app-container space-y-6 py-8 sm:py-10">
        @if($updated)
            <x-rate-freshness :updated="$updated" />
        @endif

        <section class="app-surface overflow-hidden" aria-labelledby="comparisons-heading">
            <h2 id="comparisons-heading" class="sr-only">{{ __('ui.compare_index.all') }}</h2>
            <div class="relative overflow-x-auto" tabindex="0" role="group" aria-labelledby="comparisons-heading">
                <table class="app-table sm:min-w-[36rem]">
                    <thead>
                        <tr>
                            <th scope="col" class="pl-5 sm:pl-6">{{ __('ui.compare_index.countries') }}</th>
                            <th scope="col" class="text-right">{{ __('ui.compare.standard_rate') }}</th>
                            <th scope="col" class="hidden pr-5 sm:table-cell sm:pr-6">{{ __('ui.compare_index.difference') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($comparisons as $comparison)
                            @php
                                $difference = round((float) $comparison['left']->standard_rate - (float) $comparison['right']->standard_rate, 2);
                                $higher = $difference > 0 ? $comparison['left'] : ($difference < 0 ? $comparison['right'] : null);
                            @endphp
                            <tr class="group">
                                <th scope="row" class="pl-5 sm:pl-6">
                                    <a href="{{ $comparison['url'] }}" class="flex items-center gap-3 font-display text-base font-medium text-ink underline decoration-transparent underline-offset-4 transition-colors group-hover:decoration-action">
                                        <span class="flex shrink-0 -space-x-1.5" aria-hidden="true">
                                            <x-ui.flag :iso="$comparison['left']->iso_code" size="sm" />
                                            <x-ui.flag :iso="$comparison['right']->iso_code" size="sm" />
                                        </span>
                                        <span class="min-w-0">{{ $comparison['label'] }}</span>
                                    </a>
                                </th>
                                <td class="tabular whitespace-nowrap text-right font-display text-base font-semibold text-ink">
                                    {{ Country::formatRate($comparison['left']->standard_rate) }}%
                                    <span class="px-1 font-normal text-ink-quiet">/</span>
                                    {{ Country::formatRate($comparison['right']->standard_rate) }}%
                                </td>
                                <td class="hidden pr-5 text-ink-muted sm:table-cell sm:pr-6">
                                    @if($higher)
                                        <span class="inline-flex items-center gap-2">
                                            <x-ui.flag :iso="$higher->iso_code" size="xs" />
                                            {{ __('ui.compare.higher_by', ['points' => Country::formatRate(abs($difference))]) }}
                                        </span>
                                    @else
                                        {{ __('ui.compare.same_rate') }}
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        <p class="max-w-[64ch] text-sm leading-6 text-ink-muted">{{ __('ui.compare.disclaimer') }}</p>
    </div>
</div>
