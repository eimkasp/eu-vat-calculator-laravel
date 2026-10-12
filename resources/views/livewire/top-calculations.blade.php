@use('App\Livewire\SharedCalculation')
@use('App\Models\Country')
@use('App\Support\Vat\Money')
@use('App\Support\Vat\VatCalculation')

@php
    $countries = $this->countries;
    $rates = array_column($countries, 'standard_rate');
@endphp

@section('seo')
    <x-seo-meta :title="__('ui.top_calc.page_title')" :description="__('ui.top_calc.page_description')" type="website">
        <x-json-ld :data="[
            '@type' => 'ItemList',
            'name' => __('ui.top_calc.page_title'),
            'numberOfItems' => count($amounts),
            'itemListElement' => collect($amounts)->values()->map(fn (int $amount, int $index) => [
                '@type' => 'ListItem',
                'position' => $index + 1,
                'name' => __('ui.top_calc.vat_on_amount', ['amount' => number_format($amount)]),
                'url' => url(locale_path('/top-vat-calculations/'.$amount)),
            ])->all(),
        ]" />
    </x-seo-meta>
@endsection

<div>
    <x-page-header :title="__('ui.top_calc.heading')" :description="__('ui.top_calc.subheading')" :eyebrow="__('ui.nav.vat_calculator')" :breadcrumbs="[__('ui.top_calc.breadcrumb') => '']" />

    <div class="app-container space-y-8 py-8 sm:py-10">
        <nav aria-label="{{ __('ui.top_calc.amounts_label') }}" class="flex flex-wrap gap-2">
            @foreach($amounts as $option)
                <a href="{{ locale_path('/top-vat-calculations/'.$option) }}" class="app-chip tabular">{{ Money::format($option, 'EUR', decimals: 0) }}</a>
            @endforeach
        </nav>

        <section class="app-surface overflow-hidden" aria-labelledby="matrix-heading">
            <div class="border-b border-line px-5 py-4 sm:px-6">
                <h2 id="matrix-heading" class="text-lg font-bold text-ink">{{ __('ui.top_calc.total_incl_vat') }}</h2>
                <p class="mt-1 text-sm text-ink-muted">{{ __('ui.top_calc.matrix_caption') }}</p>
            </div>
            <div class="relative overflow-x-auto" tabindex="0" role="region" aria-labelledby="matrix-heading">
                <table class="app-table min-w-[56rem]">
                    <thead>
                        <tr>
                            <th scope="col" class="sticky left-0 z-10 pl-5 sm:pl-6">{{ __('ui.top_calc.country') }}</th>
                            <th scope="col" class="text-right">{{ __('ui.top_calc.rate') }}</th>
                            @foreach($amounts as $option)
                                <th scope="col" class="text-right last:pr-5 sm:last:pr-6">
                                    <a href="{{ locale_path('/top-vat-calculations/'.$option) }}" class="tabular hover:text-action">{{ Money::format($option, 'EUR', decimals: 0) }}</a>
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($countries as $country)
                            <tr class="group">
                                <th scope="row" class="sticky left-0 z-10 bg-surface pl-5 font-normal group-hover:bg-surface-subtle sm:pl-6">
                                    <a href="{{ locale_path('/vat-calculator/'.$country['slug']) }}" class="flex items-center gap-2.5 font-semibold whitespace-nowrap text-ink hover:text-action">
                                        <x-ui.flag :iso="$country['iso_code']" />
                                        {{ $country['name'] }}
                                    </a>
                                </th>
                                <td class="tabular text-right font-semibold text-ink group-hover:bg-surface-subtle">{{ Country::formatRate($country['standard_rate']) }}%</td>
                                @foreach($amounts as $option)
                                    <td class="tabular text-right group-hover:bg-surface-subtle last:pr-5 sm:last:pr-6">
                                        <a href="{{ SharedCalculation::calculationUrl($country['slug'], $option, $country['standard_rate'], 'exclude') }}" class="text-ink-muted hover:text-action hover:underline">{{ Money::format(VatCalculation::addVat($option, $country['standard_rate'])->gross, 'EUR') }}</a>
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        <section class="app-surface max-w-3xl p-5 sm:p-6" aria-labelledby="about-calculations">
            <h2 id="about-calculations" class="text-lg font-bold text-ink">{{ __('ui.top_calc.about_title') }}</h2>
            <div class="mt-3 space-y-3 text-sm leading-6 text-ink-muted">
                <p>{{ __('ui.top_calc.about_p1') }}</p>
                <p>
                    {{ __('ui.top_calc.about_p2', ['min' => Country::formatRate($rates ? min($rates) : 0), 'max' => Country::formatRate($rates ? max($rates) : 0)]) }}
                    {!! __('ui.top_calc.use_calculator', ['link' => '<a href="'.e(locale_path('/vat-calculator')).'" class="app-link">'.e(__('ui.top_calc.calculator_link_text')).'</a>']) !!}
                </p>
            </div>
        </section>

        <p class="max-w-[72ch] text-xs leading-5 text-ink-muted">{{ __('ui.top_calc.disclaimer') }}</p>
    </div>
</div>
