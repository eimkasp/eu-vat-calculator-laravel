@use('App\Models\Country')
@use('App\Support\EuropeMapSvg')

@section('seo')
    <x-seo-meta :title="__('ui.map.title').' - EU VAT Info'" :description="__('ui.map.meta_desc')" :url="url()->current()">
        @if($ranked->isNotEmpty())
            <x-json-ld :data="[
                '@type' => 'WebPage',
                'name' => __('ui.map.title'),
                'description' => __('ui.map.subtitle'),
                'url' => url(locale_path('/vat-map')),
                'inLanguage' => app()->getLocale(),
                'dateModified' => $ranked->max('updated_at')?->toIso8601String(),
                'isPartOf' => ['@type' => 'WebSite', 'name' => __('ui.site_name'), 'url' => url(locale_path('/'))],
                'mainEntity' => [
                    '@type' => 'ItemList',
                    'name' => __('ui.map.all_rates'),
                    'itemListOrder' => 'https://schema.org/ItemListOrderDescending',
                    'numberOfItems' => $ranked->count(),
                    'itemListElement' => $ranked->values()->map(fn (Country $country, int $index) => [
                        '@type' => 'ListItem',
                        'position' => $index + 1,
                        'name' => $country->name.': '.Country::formatRate($country->standard_rate).'%',
                        'url' => url(locale_path('/vat-calculator/'.$country->slug)),
                    ])->all(),
                ],
            ]" />
        @endif
    </x-seo-meta>
@endsection

<div>
    <x-page-header :title="__('ui.map.title')" :description="__('ui.map.subtitle')" :eyebrow="__('ui.nav.vat_tools')" :breadcrumbs="[__('ui.breadcrumbs.vat_map') => '']" />

    <div class="app-container space-y-8 py-8 sm:py-10">
        @if($ranked->isNotEmpty())
            <x-rate-freshness :updated="$ranked->max('updated_at')" class="-mb-4" />
        @endif
        @if($countries->isEmpty())
            <div class="flex items-start gap-3 rounded-card border border-warning/30 bg-warning-soft p-4" role="status">
                <x-ui.icon name="info" class="mt-0.5 size-5 text-warning" />
                <div>
                    <p class="font-semibold text-ink">{{ __('ui.errors.data_load_failed_title') }}</p>
                    <p class="text-sm text-ink-muted">{{ __('ui.errors.data_load_failed_desc') }}</p>
                </div>
            </div>
        @else
            <section class="app-surface p-4 sm:p-6">
                <x-europe-map :countries="$countries" />
            </section>

            <section class="app-surface overflow-hidden" aria-labelledby="map-rates-heading">
                <div class="flex flex-wrap items-end justify-between gap-3 border-b border-line px-5 py-4 sm:px-6">
                    <div>
                        <h2 id="map-rates-heading" class="text-lg font-bold text-ink">{{ __('ui.map.all_rates') }}</h2>
                        <p class="mt-1 text-sm text-ink-muted">{{ __('ui.map.table_intro') }}</p>
                    </div>
                </div>
                <div class="relative overflow-x-auto" tabindex="0" role="region" aria-labelledby="map-rates-heading">
                    <table class="app-table min-w-[36rem]">
                        <thead>
                            <tr>
                                <th scope="col" class="pl-5 sm:pl-6">{{ __('ui.map.th_country') }}</th>
                                <th scope="col">{{ __('ui.map.th_standard') }}</th>
                                <th scope="col">{{ __('ui.map.th_reduced') }}</th>
                                <th scope="col">{{ __('ui.map.th_super_reduced') }}</th>
                                <th scope="col" class="pr-5 text-right sm:pr-6"><span class="sr-only">{{ __('ui.actions') }}</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($ranked as $country)
                                <tr class="transition-colors hover:bg-surface-subtle">
                                    <td class="pl-5 sm:pl-6">
                                        <a href="{{ locale_path('/vat-calculator/'.$country->slug) }}" class="flex min-h-10 items-center gap-3 font-semibold text-ink hover:text-action">
                                            <x-ui.flag :iso="$country->iso_code" />
                                            {{ $country->name }}
                                        </a>
                                    </td>
                                    <td>
                                        <span class="inline-flex items-center gap-2">
                                            <span class="eu-map-swatch eu-map-swatch-{{ EuropeMapSvg::bucket((float) $country->standard_rate) }}" aria-hidden="true"></span>
                                            <span class="tabular font-bold text-ink">{{ Country::formatRate($country->standard_rate) }}%</span>
                                        </span>
                                    </td>
                                    <td class="tabular text-ink-muted">{{ $country->formattedReducedRates() ?? '—' }}</td>
                                    <td class="tabular text-ink-muted">{{ (float) $country->super_reduced_rate > 0 ? Country::formatRate($country->super_reduced_rate).'%' : '—' }}</td>
                                    <td class="pr-5 text-right sm:pr-6">
                                        <a href="{{ locale_path('/vat-calculator/'.$country->slug) }}" class="app-link text-sm">{{ __('ui.map.calculator_link') }}</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @endif

        <div class="grid gap-6 md:grid-cols-2">
            <section class="app-surface p-5 sm:p-6">
                <h2 class="text-lg font-bold text-ink">{{ __('ui.map.understanding') }}</h2>
                <p class="mt-2 text-sm leading-6 text-ink-muted">{{ __('ui.map.understanding_desc') }}</p>
                <ul class="mt-4 space-y-2 text-sm text-ink-muted">
                    @foreach(['standard_range', 'reduced_essentials', 'special_schemes'] as $key)
                        <li class="flex gap-2.5"><x-ui.icon name="check" class="mt-0.5 size-4 text-success" />{{ __('ui.map.'.$key) }}</li>
                    @endforeach
                </ul>
            </section>
            <section class="app-surface p-5 sm:p-6">
                <h2 class="text-lg font-bold text-ink">{{ __('ui.map.using_calculator') }}</h2>
                <p class="mt-2 text-sm leading-6 text-ink-muted">{{ __('ui.map.using_calc_desc') }}</p>
                <ul class="mt-4 space-y-2 text-sm text-ink-muted">
                    @foreach(['calc_inclusive', 'view_rate_types', 'validate_vat'] as $key)
                        <li class="flex gap-2.5"><x-ui.icon name="check" class="mt-0.5 size-4 text-success" />{{ __('ui.map.'.$key) }}</li>
                    @endforeach
                </ul>
            </section>
        </div>
    </div>
</div>
