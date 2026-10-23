@props(['country', 'headingLevel' => 1, 'breadcrumbLabel' => null])

@use('App\Models\Country')

<header data-country-header class="text-ink">
    <x-site-breadcrumbs :items="[__('ui.calculator.breadcrumb_label') => locale_path('/vat-calculator'), $country->name => '']" :aria-label="$breadcrumbLabel ?? __('ui.breadcrumbs.label')" />

    <div class="mt-6 flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
        <div class="min-w-0">
            <div class="flex items-center gap-4">
                <x-ui.flag :iso="$country->iso_code" size="xl" :lazy="false" :alt="$country->name" class="shrink-0" />
                <h{{ $headingLevel }} class="text-3xl font-semibold tracking-[-0.03em] text-ink sm:text-[2.75rem] sm:leading-[1.05]">{{ __('ui.calculator.country_heading', ['country' => $country->name]) }}</h{{ $headingLevel }}>
            </div>
            <p class="mt-3 max-w-[52ch] text-sm leading-6 text-ink-muted sm:text-base sm:leading-7">
                {{ __('ui.calculator.country_subtitle', ['country' => $country->name, 'rate' => Country::formatRate($country->standard_rate)]) }}
            </p>
            <p class="mt-2 inline-flex items-center gap-1.5 font-display text-[0.8125rem] font-medium text-ink-muted">
                <x-ui.icon :name="$country->is_eu_member ? 'shield-check' : 'globe'" class="size-3.5 text-action" />
                {{ $country->is_eu_member ? __('ui.calculator.scope.eu') : __('ui.calculator.scope.other_europe') }}
            </p>
        </div>

        <dl class="hidden shrink-0 grid-cols-3 divide-x divide-line overflow-hidden rounded-card border border-line bg-surface sm:grid">
            <div class="flex flex-col justify-center px-5 py-3">
                <dt class="font-display text-[0.8125rem] font-medium text-ink-muted">{{ __('ui.rate_type.standard') }}</dt>
                <dd class="tabular mt-1 font-display text-3xl font-semibold tracking-[-0.02em] text-ink">{{ Country::formatRate($country->standard_rate) }}%</dd>
            </div>
            <div class="flex flex-col justify-center px-5 py-3">
                <dt class="font-display text-[0.8125rem] font-medium text-ink-muted">{{ __('ui.rate_type.reduced') }}</dt>
                <dd class="tabular mt-1 flex flex-wrap gap-x-3 font-display text-3xl font-semibold tracking-[-0.02em] text-ink">
                    @forelse($country->reducedRates() as $rate)
                        <span>{{ Country::formatRate($rate) }}%</span>
                    @empty
                        <span>—</span>
                    @endforelse
                </dd>
            </div>
            <div class="flex flex-col justify-center px-5 py-3">
                <dt class="font-display text-[0.8125rem] font-medium text-ink-muted">{{ __('ui.country_page.currency') }}</dt>
                <dd class="mt-1 font-mono text-3xl font-medium text-ink">{{ $country->currencyCode() }}</dd>
            </div>
        </dl>
    </div>
</header>
