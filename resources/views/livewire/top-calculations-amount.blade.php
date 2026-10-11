@use('App\Livewire\SharedCalculation')
@use('App\Models\Country')
@use('App\Support\Vat\Money')
@use('App\Support\Vat\VatCalculation')

@php
    $countries = $this->countries;
    $rates = array_column($countries, 'standard_rate');
    $heading = __('ui.top_calc.vat_on_amount', ['amount' => number_format($amount)]);
    $description = __('ui.top_calc.amount_page_desc', [
        'amount' => number_format($amount),
        'count' => count($countries),
        'member_label' => __('ui.top_calc.member_states'),
        'min' => Country::formatRate($rates ? min($rates) : 0),
        'max' => Country::formatRate($rates ? max($rates) : 0),
    ]);
    $maxVat = max([0.01, ...array_map(fn (float $rate) => VatCalculation::addVat($amount, $rate)->vat, $rates)]);
@endphp

@section('seo')
    <x-seo-meta :title="__('ui.top_calc.amount_meta_title', ['amount' => number_format($amount)])" :description="$description" type="website">
        <x-json-ld :data="[
            '@type' => 'ItemList',
            'name' => $heading,
            'numberOfItems' => count($countries),
            'itemListElement' => collect($countries)->values()->map(fn (array $country, int $index) => [
                '@type' => 'ListItem',
                'position' => $index + 1,
                'name' => $country['name'].': '.Country::formatRate($country['standard_rate']).'%',
                'url' => url(SharedCalculation::calculationUrl($country['slug'], $amount, $country['standard_rate'], 'exclude')),
            ])->all(),
        ]" />
    </x-seo-meta>
@endsection

<div>
    <x-page-header :title="$heading" :description="$description" :eyebrow="__('ui.top_calc.heading')" :breadcrumbs="[__('ui.top_calc.breadcrumb') => locale_path('/top-vat-calculations'), $heading => '']" />

    <div class="app-container space-y-8 py-8 sm:py-10">
        <x-rate-freshness :updated="Country::query()->where('is_eu_member', true)->max('updated_at')" class="-mb-4" />
        <nav aria-label="{{ __('ui.top_calc.amounts_label') }}" class="flex flex-wrap gap-2">
            @foreach($amounts as $option)
                <a href="{{ locale_path('/top-vat-calculations/'.$option) }}" @if($option === $amount) aria-current="page" @endif @class(['app-chip tabular', 'app-chip-active' => $option === $amount])>{{ Money::format($option, 'EUR', decimals: 0) }}</a>
            @endforeach
        </nav>

        <section class="app-surface overflow-hidden" aria-labelledby="amount-table">
            <h2 id="amount-table" class="sr-only">{{ $heading }}</h2>
            <div class="relative overflow-x-auto">
                <table class="app-table min-w-[52rem]">
                    <thead>
                        <tr>
                            <th scope="col" rowspan="2" class="pl-5 align-bottom sm:pl-6">{{ __('ui.top_calc.country') }}</th>
                            <th scope="col" rowspan="2" class="text-right align-bottom">{{ __('ui.top_calc.rate') }}</th>
                            <th scope="colgroup" colspan="3" class="border-l border-line pb-1 text-center">{{ __('ui.top_calc.adding', ['amount' => number_format($amount)]) }}</th>
                            <th scope="colgroup" colspan="2" class="border-l border-line pb-1 pr-5 text-center sm:pr-6">{{ __('ui.top_calc.removing', ['amount' => number_format($amount)]) }}</th>
                        </tr>
                        <tr>
                            <th scope="col" class="border-l border-line pt-1 text-right">{{ __('ui.top_calc.vat_amount') }}</th>
                            <td class="border-t-0 bg-surface-subtle pt-1" aria-hidden="true"></td>
                            <th scope="col" class="pt-1 text-right">{{ __('ui.top_calc.total_incl_vat') }}</th>
                            <th scope="col" class="border-l border-line pt-1 text-right">{{ __('ui.top_calc.net') }}</th>
                            <th scope="col" class="pt-1 pr-5 text-right sm:pr-6">{{ __('ui.top_calc.vat_amount') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($countries as $country)
                            @php
                                $add = VatCalculation::addVat($amount, $country['standard_rate']);
                                $remove = VatCalculation::removeVat($amount, $country['standard_rate']);
                            @endphp
                            <tr class="transition-colors hover:bg-surface-subtle">
                                <td class="pl-5 sm:pl-6">
                                    <a href="{{ locale_path('/vat-calculator/'.$country['slug']) }}" class="flex items-center gap-2.5 font-semibold whitespace-nowrap text-ink hover:text-action">
                                        <x-ui.flag :iso="$country['iso_code']" />
                                        {{ $country['name'] }}
                                    </a>
                                </td>
                                <td class="tabular text-right font-semibold text-ink">{{ Country::formatRate($country['standard_rate']) }}%</td>
                                <td class="tabular border-l border-line text-right text-ink-muted">{{ Money::format($add->vat, 'EUR') }}</td>
                                <td class="w-24" aria-hidden="true">
                                    <span class="block h-1.5 overflow-hidden rounded-xs bg-action-soft"><span class="block h-full bg-action" style="width: {{ round($add->vat / $maxVat * 100) }}%"></span></span>
                                </td>
                                <td class="tabular text-right">
                                    <a href="{{ SharedCalculation::calculationUrl($country['slug'], $amount, $country['standard_rate'], 'exclude') }}" class="font-semibold text-ink hover:text-action hover:underline">{{ Money::format($add->gross, 'EUR') }}</a>
                                </td>
                                <td class="tabular border-l border-line text-right">
                                    <a href="{{ SharedCalculation::calculationUrl($country['slug'], $amount, $country['standard_rate'], 'include') }}" class="font-semibold text-ink hover:text-action hover:underline">{{ Money::format($remove->net, 'EUR') }}</a>
                                </td>
                                <td class="tabular pr-5 text-right text-ink-muted sm:pr-6">{{ Money::format($remove->vat, 'EUR') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        <p class="max-w-[72ch] text-xs leading-5 text-ink-muted">{{ __('ui.top_calc.disclaimer') }}</p>
    </div>
</div>
