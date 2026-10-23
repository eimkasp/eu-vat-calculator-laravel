@props(['country'])

@use('App\Livewire\SharedCalculation')
@use('App\Livewire\TopCalculations')
@use('App\Models\Country')
@use('App\Support\Vat\Money')
@use('App\Support\Vat\VatCalculation')

@php
    $rate = (float) $country->standard_rate;
    $currency = $country->currencyCode();
@endphp

@if($rate > 0)
    <section data-common-amounts aria-labelledby="common-amounts-title" class="app-surface app-deferred overflow-hidden">
        <div class="border-b border-line px-5 py-4 sm:px-6">
            <h2 id="common-amounts-title" class="text-xl font-semibold text-ink">{{ __('ui.calculator.common_amounts_heading', ['country' => $country->name]) }}</h2>
            <p class="mt-1 max-w-[72ch] text-sm text-ink-muted">{{ __('ui.calculator.common_amounts_intro', ['rate' => Country::formatRate($rate)]) }}</p>
        </div>
        <div>
            <table class="app-table">
                <thead>
                    <tr>
                        <th scope="col" class="pl-4 sm:pl-6">{{ __('ui.calculator.th_amount') }}</th>
                        <th scope="col" class="text-right">{{ __('ui.calculator.th_add_vat', ['rate' => Country::formatRate($rate)]) }}</th>
                        <th scope="col" class="pr-4 text-right sm:pr-6">{{ __('ui.calculator.th_remove_vat', ['rate' => Country::formatRate($rate)]) }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach(TopCalculations::AMOUNTS as $amount)
                        @php
                            $add = VatCalculation::addVat($amount, $rate);
                            $remove = VatCalculation::removeVat($amount, $rate);
                        @endphp
                        <tr>
                            <th scope="row" class="tabular pl-4 font-semibold text-ink sm:pl-6">{{ Money::format($amount, $currency, decimals: 0) }}</th>
                            <td class="text-right">
                                <a href="{{ SharedCalculation::calculationUrl($country->slug, $amount, $rate, 'exclude') }}" class="tabular inline-flex min-h-10 flex-col items-end justify-center font-semibold text-ink hover:text-action sm:flex-row sm:items-center sm:gap-2">
                                    <span class="text-xs font-medium text-ink-muted">+{{ Money::format($add->vat, $currency) }}</span>
                                    {{ Money::format($add->gross, $currency) }}
                                </a>
                            </td>
                            <td class="pr-4 text-right sm:pr-6">
                                <a href="{{ SharedCalculation::calculationUrl($country->slug, $amount, $rate, 'include') }}" class="tabular inline-flex min-h-10 flex-col items-end justify-center font-semibold text-ink hover:text-action sm:flex-row sm:items-center sm:gap-2">
                                    <span class="text-xs font-medium text-ink-muted">−{{ Money::format($remove->vat, $currency) }}</span>
                                    {{ Money::format($remove->net, $currency) }}
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
@endif
