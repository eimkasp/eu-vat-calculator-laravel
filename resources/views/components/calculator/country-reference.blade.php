@props(['country', 'since' => null])

@use('App\Models\Country')
@use('App\Support\Vat\Money')
@use('App\Support\Vat\VatCalculation')
@use('App\Support\Vat\VatNumberFormat')

@php
    $rates = collect($country->rateOptions());
    $standard = (float) $country->standard_rate;
    $multiplier = Country::formatRate(1 + $standard / 100);
    $currency = $country->currencyCode();
    $add = VatCalculation::addVat(100, $standard);
    $remove = VatCalculation::removeVat(100, $standard);
    $since ??= $country->standardRateSince();
    $format = $country->vies_available ? config('vat-number-formats.'.strtoupper((string) $country->iso_code)) : null;
    $historyUrl = $country->is_eu_member && $country->hasVatHistory() ? locale_path('/vat-rates/'.$country->slug.'/history') : null;
@endphp

<section data-country-reference aria-labelledby="country-reference-title" class="app-surface overflow-hidden">
    <div class="flex flex-wrap items-end justify-between gap-x-6 gap-y-3 border-b border-line px-5 py-4 sm:px-6">
        <div>
            <h2 id="country-reference-title" class="text-xl font-semibold text-ink">{{ __('ui.calculator.rates_and_formulas', ['country' => $country->name]) }}</h2>
        </div>
        <x-rate-freshness :updated="$country->updated_at" :eu="$country->is_eu_member" />
    </div>

    <div class="grid grid-cols-1 md:grid-cols-[minmax(0,1.15fr)_minmax(0,1fr)]">
        <div class="border-b border-line p-5 sm:p-6 md:border-b-0 md:border-r">
            <h3 class="text-sm font-semibold text-ink">{{ __('ui.calculator.rate_reference') }}</h3>
            <dl class="mt-2 divide-y divide-line">
                @foreach($rates as $rate)
                    <div class="flex items-start justify-between gap-4 py-3">
                        <dt class="min-w-0">
                            <span class="block text-sm font-semibold text-ink">{{ __('ui.rate_type.'.$rate['type']) }}</span>
                            <span class="mt-0.5 block text-xs leading-5 text-ink-muted">{{ __('ui.rate_type_desc.'.$rate['type']) }}</span>
                            @if($rate['type'] === 'standard' && $since)
                                <span class="mt-1 block text-xs leading-5 text-ink-quiet">
                                    {{ $since['previous'] !== null
                                        ? __('ui.calculator.rate_since_previous', ['date' => $since['since']->locale(app()->getLocale())->translatedFormat('j M Y'), 'rate' => Country::formatRate($since['previous'])])
                                        : __('ui.calculator.rate_since', ['date' => $since['since']->locale(app()->getLocale())->translatedFormat('j M Y')]) }}
                                </span>
                            @endif
                        </dt>
                        <dd class="tabular shrink-0 font-display text-xl font-semibold text-ink">{{ Country::formatRate($rate['rate']) }}%</dd>
                    </div>
                @endforeach
            </dl>

            <dl class="mt-4 grid grid-cols-2 gap-x-5 gap-y-4 rounded-control border border-line bg-surface-subtle p-4 text-sm">
                <div>
                    <dt class="font-display text-[0.8125rem] font-medium text-ink-muted">{{ __('ui.country_page.currency') }}</dt>
                    <dd class="mt-1 flex flex-wrap items-baseline gap-x-2 font-semibold text-ink">
                        <span class="font-mono">{{ $currency }}</span>
                        <span>{{ $country->currency_display }}</span>
                    </dd>
                </div>
                <div>
                    <dt class="font-display text-[0.8125rem] font-medium text-ink-muted">{{ __('ui.country_page.eu_member') }}</dt>
                    <dd class="mt-1 font-semibold text-ink">{{ $country->is_eu_member ? __('ui.country_page.yes') : __('ui.country_page.no') }}</dd>
                </div>
                @if($format)
                    <div class="col-span-2">
                        <dt class="font-display text-[0.8125rem] font-medium text-ink-muted">{{ __('ui.calculator.vat_number_format') }}</dt>
                        <dd class="mt-1 font-mono text-sm font-semibold break-all text-ink">{{ VatNumberFormat::describe($format) }}</dd>
                    </div>
                @endif
            </dl>

            @if($historyUrl)
                <a href="{{ $historyUrl }}" class="app-link mt-4 inline-flex items-center gap-1.5 text-sm font-semibold">
                    <x-ui.icon name="history" class="size-4" />
                    {{ __('ui.calculator.full_rate_history', ['country' => $country->name]) }}
                </a>
            @endif
        </div>

        <div class="p-5 sm:p-6">
            <h3 class="text-sm font-semibold text-ink">{{ __('ui.calculator.calculation_reference') }}</h3>
            <div class="mt-3 space-y-3">
                <div class="rounded-control border border-line bg-surface p-4">
                    <p class="text-sm font-semibold text-ink">{{ __('ui.country_page.adding_vat', ['rate' => Country::formatRate($standard)]) }}</p>
                    <code class="mt-2 block font-mono text-sm font-semibold text-action-deep">{{ __('ui.calculator.formula_add', ['multiplier' => $multiplier]) }}</code>
                    <p class="tabular mt-2 text-sm text-ink-muted">{{ Money::format($add->net, $currency) }} → <strong class="text-ink">{{ Money::format($add->gross, $currency) }}</strong></p>
                </div>
                <div class="rounded-control border border-line bg-surface p-4">
                    <p class="text-sm font-semibold text-ink">{{ __('ui.country_page.removing_vat', ['rate' => Country::formatRate($standard)]) }}</p>
                    <code class="mt-2 block font-mono text-sm font-semibold text-action-deep">{{ __('ui.calculator.formula_remove', ['multiplier' => $multiplier]) }}</code>
                    <p class="tabular mt-2 text-sm text-ink-muted">{{ Money::format($remove->gross, $currency) }} → <strong class="text-ink">{{ Money::format($remove->net, $currency) }}</strong></p>
                </div>
                <p class="text-xs leading-5 text-ink-muted">{{ __('ui.calculator.formula_vat_part', ['rate' => Country::formatRate($standard), 'divisor' => Country::formatRate(100 + $standard)]) }}</p>
            </div>
        </div>
    </div>
</section>
