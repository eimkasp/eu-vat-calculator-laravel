@props(['country'])

@use('App\Models\Country')

<header data-country-header class="text-white">
    <x-site-breadcrumbs variant="dark" :items="[__('ui.calculator.breadcrumb_label') => locale_path('/vat-calculator'), $country->name => '']" />

    <div class="mt-6 flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
        <div class="flex min-w-0 items-start justify-between gap-4 sm:justify-start">
            <x-ui.flag :iso="$country->iso_code" size="xl" :lazy="false" :alt="$country->name" class="order-last mt-1 shrink-0 shadow-none ring-1 ring-white/25 sm:order-none" />
            <div class="min-w-0 flex-1 sm:flex-none">
                <p class="app-kicker">{{ $country->is_eu_member ? __('ui.calculator.scope.eu') : __('ui.calculator.scope.other_europe') }}</p>
                <h1 class="mt-2 text-3xl font-bold tracking-[-0.03em] text-white sm:text-4xl">{{ __('ui.calculator.country_heading', ['country' => $country->name]) }}</h1>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-white/80 sm:text-base">
                    {{ __('ui.calculator.country_subtitle', ['country' => $country->name, 'rate' => Country::formatRate($country->standard_rate)]) }}
                </p>
            </div>
        </div>

        <dl class="app-brand-panel grid shrink-0 grid-cols-3 divide-x divide-white/15 overflow-hidden text-center">
            <div class="px-4 py-3 sm:px-5">
                <dt class="text-[0.6875rem] font-semibold tracking-[0.08em] text-white/70 uppercase">{{ __('ui.rate_type.standard') }}</dt>
                <dd class="tabular mt-1 text-2xl font-bold text-white">{{ Country::formatRate($country->standard_rate) }}%</dd>
            </div>
            <div class="px-4 py-3 sm:px-5">
                <dt class="text-[0.6875rem] font-semibold tracking-[0.08em] text-white/70 uppercase">{{ __('ui.rate_type.reduced') }}</dt>
                <dd class="tabular mt-1 text-2xl font-bold text-white">{{ $country->formattedReducedRates(' · ') ?? '—' }}</dd>
            </div>
            <div class="px-4 py-3 sm:px-5">
                <dt class="text-[0.6875rem] font-semibold tracking-[0.08em] text-white/70 uppercase">{{ __('ui.country_page.currency') }}</dt>
                <dd class="tabular mt-1 text-2xl font-bold text-white">{{ $country->currencyCode() }}</dd>
            </div>
        </dl>
    </div>
</header>
