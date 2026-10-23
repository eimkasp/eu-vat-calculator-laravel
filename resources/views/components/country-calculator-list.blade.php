@php
    $groupLabels = [
        'eu' => __('ui.calculator.groups.eu'),
        'other_europe' => __('ui.calculator.groups.other_europe'),
    ];
    $countryCount = $countries->flatten(1)->count();
@endphp

<details data-country-directory class="app-surface group overflow-hidden">
    <summary class="flex min-h-14 cursor-pointer list-none items-center gap-3 px-5 py-3 text-left [&::-webkit-details-marker]:hidden">
        <x-ui.icon name="globe" class="size-4 text-action" />
        <span class="flex-1 font-display text-sm font-semibold text-ink">{{ __('ui.calculator.browse_all_country_calculators') }}</span>
        <span class="app-badge tabular bg-surface-muted text-ink-muted">{{ $countryCount }}</span>
        <x-ui.icon name="chevron-down" class="size-4 text-ink-muted transition-transform duration-150 group-open:rotate-180" />
    </summary>

    <div class="border-t border-line px-3 pb-4 pt-3">
        @foreach($groupLabels as $group => $label)
            @if(($countries[$group] ?? collect())->isNotEmpty())
                <section @class(['mt-4 border-t border-line pt-3' => ! $loop->first]) aria-labelledby="calculator-group-{{ $group }}">
                    <h3 id="calculator-group-{{ $group }}" class="px-2 font-display text-[0.8125rem] font-medium text-ink-muted">{{ $label }}</h3>
                    <div class="mt-1.5 grid grid-cols-1 gap-0.5 sm:grid-cols-2 xl:grid-cols-1 2xl:grid-cols-2">
                        @foreach($countries[$group] as $country)
                            <a href="{{ locale_path('/vat-calculator/'.$country->slug) }}" class="group/link flex min-h-10 items-center gap-2.5 rounded-control px-2 text-sm transition-colors hover:bg-surface-subtle">
                                <x-ui.flag :iso="$country->iso_code" size="sm" />
                                <span class="min-w-0 flex-1 truncate font-medium text-ink group-hover/link:text-action">{{ $country->name }}</span>
                                <span class="tabular text-xs font-semibold text-ink-muted">{{ \App\Models\Country::formatRate($country->standard_rate) }}%</span>
                            </a>
                        @endforeach
                    </div>
                </section>
            @endif
        @endforeach
    </div>
</details>
