@props(['country', 'headingLevel' => 1, 'breadcrumbLabel' => null])

@use('App\Models\Country')

<header data-country-header class="text-white">
    <x-site-breadcrumbs variant="dark" :items="[__('ui.calculator.breadcrumb_label') => locale_path('/vat-calculator'), $country->name => '']" :aria-label="$breadcrumbLabel ?? __('ui.breadcrumbs.label')" />

    <div class="mt-6 flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
        <div class="flex min-w-0 items-start justify-between gap-4">
            <div class="min-w-0">
                <p class="flex items-center gap-3">
                    <x-ui.flag :iso="$country->iso_code" size="lg" :lazy="false" :alt="$country->name" class="hidden shadow-none ring-1 ring-white/25 sm:block" />
                    <span class="app-kicker sm:before:hidden">{{ $country->is_eu_member ? __('ui.calculator.scope.eu') : __('ui.calculator.scope.other_europe') }}</span>
                </p>
                <h{{ $headingLevel }} class="mt-2 text-3xl font-bold tracking-[-0.03em] text-white sm:text-4xl">{{ __('ui.calculator.country_heading', ['country' => $country->name]) }}</h{{ $headingLevel }}>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-white/80 sm:text-base">
                    {{ __('ui.calculator.country_subtitle', ['country' => $country->name, 'rate' => Country::formatRate($country->standard_rate)]) }}
                </p>
            </div>
            <x-ui.flag :iso="$country->iso_code" size="xl" :lazy="false" :alt="$country->name" class="mt-1 shrink-0 shadow-none ring-1 ring-white/25 sm:hidden" />
        </div>

        <dl class="app-brand-panel hidden shrink-0 grid-cols-3 divide-x divide-white/15 overflow-hidden text-center sm:grid">
            <div class="flex flex-col justify-center px-4 py-3 sm:px-5">
                <dt class="text-[0.6875rem] font-semibold tracking-[0.08em] text-white/70 uppercase">{{ __('ui.rate_type.standard') }}</dt>
                <dd class="tabular mt-1 text-2xl font-bold text-white">{{ Country::formatRate($country->standard_rate) }}%</dd>
            </div>
            <div class="flex flex-col justify-center px-4 py-3 sm:px-5">
                <dt class="text-[0.6875rem] font-semibold tracking-[0.08em] text-white/70 uppercase">{{ __('ui.rate_type.reduced') }}</dt>
                <dd class="tabular mt-1 text-2xl font-bold text-white">
                    @forelse($country->reducedRates() as $rate)
                        <span class="block sm:inline">@unless($loop->first)<span class="hidden sm:inline" aria-hidden="true"> · </span>@endunless{{ Country::formatRate($rate) }}%</span>
                    @empty
                        —
                    @endforelse
                </dd>
            </div>
            <div class="flex flex-col justify-center px-4 py-3 sm:px-5">
                <dt class="text-[0.6875rem] font-semibold tracking-[0.08em] text-white/70 uppercase">{{ __('ui.country_page.currency') }}</dt>
                <dd class="tabular mt-1 text-2xl font-bold text-white">{{ $country->currencyCode() }}</dd>
            </div>
        </dl>
    </div>
</header>
