@props(['countries' => [], 'search' => '', 'stats' => null])

@use('App\Models\Country')

@php
    $maxRate = max(27.0, (float) collect($countries)->max('standard_rate'));
@endphp

<section
    class="app-surface overflow-hidden"
    x-data="{
        query: @js($search),
        get needle() { return this.query.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase().trim(); },
        get noMatches() { const needle = this.needle; return needle !== '' && ![...this.$root.querySelectorAll('tbody tr')].some((row) => row.dataset.search.includes(needle)); },
    }"
    x-init="$watch('query', (value) => { const url = new URL(window.location); value ? url.searchParams.set('search', value) : url.searchParams.delete('search'); history.replaceState(history.state, '', url); })"
    aria-labelledby="rates-heading">
    <div class="border-b border-line p-5 sm:p-6">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h2 id="rates-heading" class="text-xl font-bold text-ink">{{ __('ui.home_page.rates_heading') }}</h2>
                <p class="mt-1 text-sm text-ink-muted">{{ __('ui.home_page.rates_intro') }}</p>
            </div>
            <span class="app-badge bg-action-soft text-action-deep">{{ __('ui.home_page.rates_year', ['year' => date('Y')]) }}</span>
        </div>

        @if($stats)
            <dl class="mt-5 grid grid-cols-3 divide-x divide-line rounded-card border border-line bg-surface-subtle">
                <div class="px-3 py-3 sm:px-4">
                    <dt class="text-xs font-medium text-ink-muted">{{ __('ui.home_page.stat_lowest') }}</dt>
                    <dd class="mt-1 flex items-center gap-1.5 text-lg font-bold text-ink">
                        {{ Country::formatRate($stats['lowest']->standard_rate) }}%
                        <x-ui.flag :iso="$stats['lowest']->iso_code" size="xs" :alt="$stats['lowest']->name" />
                    </dd>
                </div>
                <div class="px-3 py-3 sm:px-4">
                    <dt class="text-xs font-medium text-ink-muted">{{ __('ui.home_page.stat_average') }}</dt>
                    <dd class="mt-1 text-lg font-bold text-ink">{{ Country::formatRate($stats['average']) }}%</dd>
                </div>
                <div class="px-3 py-3 sm:px-4">
                    <dt class="text-xs font-medium text-ink-muted">{{ __('ui.home_page.stat_highest') }}</dt>
                    <dd class="mt-1 flex items-center gap-1.5 text-lg font-bold text-ink">
                        {{ Country::formatRate($stats['highest']->standard_rate) }}%
                        <x-ui.flag :iso="$stats['highest']->iso_code" size="xs" :alt="$stats['highest']->name" />
                    </dd>
                </div>
            </dl>
        @endif

        <div class="relative mt-4">
            <label for="country-rate-search" class="sr-only">{{ __('ui.home_page.search_placeholder') }}</label>
            <x-ui.icon name="search" class="pointer-events-none absolute left-3.5 top-1/2 size-4 -translate-y-1/2 text-ink-quiet" />
            <input x-model.debounce.120ms="query" id="country-rate-search" type="search" autocomplete="off" placeholder="{{ __('ui.home_page.search_placeholder') }}" class="app-field pl-10">
        </div>
    </div>

    <div class="relative overflow-x-auto app-deferred [--deferred-size:136rem] sm:[--deferred-size:92rem]" tabindex="0" role="group" aria-labelledby="rates-heading">
        <table class="app-table sm:min-w-[34rem]">
            <thead>
                <tr>
                    <th scope="col" class="pl-5 sm:pl-6">{{ __('ui.home_page.th_country') }}</th>
                    <th scope="col">{{ __('ui.home_page.th_standard') }}</th>
                    <th scope="col" class="hidden sm:table-cell">{{ __('ui.home_page.th_reduced') }}</th>
                    <th scope="col" class="pr-5 text-right sm:pr-6"><span class="sr-only">{{ __('ui.home_page.th_actions') }}</span></th>
                </tr>
            </thead>
            <tbody x-effect="const value = needle; $el.querySelectorAll('tr').forEach((row) => (row.style.display = !value || row.dataset.search.includes(value) ? '' : 'none'))">
                @foreach($countries as $country)
                    @php
                        $haystack = \Illuminate\Support\Str::lower(\Illuminate\Support\Str::ascii($country->name.' '.$country->native_name.' '.$country->iso_code));
                        $matchesInitial = $search === '' || str_contains($haystack, \Illuminate\Support\Str::lower(\Illuminate\Support\Str::ascii($search)));
                    @endphp
                    <tr
                        wire:key="rate-row-{{ $country->id }}"
                        data-search="{{ $haystack }}"
                        @unless($matchesInitial) style="display: none" @endunless
                        class="group transition-colors hover:bg-surface-subtle"
                    >
                        <td class="py-1.5 pl-5 sm:pl-6">
                            <a href="{{ locale_path('/vat-calculator/'.$country->slug) }}" class="flex min-h-10 items-center gap-3 font-semibold text-ink group-hover:text-action">
                                <x-ui.flag :iso="$country->iso_code" size="md" />
                                <span class="truncate">{{ $country->name }}</span>
                            </a>
                            <div class="mb-1 flex flex-wrap items-center gap-1 pl-[2.125rem] sm:hidden">
                                <span class="sr-only">{{ __('ui.home_page.th_reduced') }}:</span>
                                @forelse($country->reducedRates() as $rate)
                                    <span class="tabular rounded-control bg-surface-muted px-1.5 py-px text-[0.6875rem] font-semibold text-ink-muted">{{ Country::formatRate($rate) }}%</span>
                                @empty
                                    <span class="text-xs text-ink-quiet">—</span>
                                @endforelse
                                @if((float) $country->super_reduced_rate > 0)
                                    <span class="tabular rounded-control border border-dashed border-line-strong px-1.5 text-[0.6875rem] font-medium text-ink-quiet" title="{{ __('ui.rate_type.super_reduced') }}"><span class="sr-only">{{ __('ui.rate_type.super_reduced') }}: </span>{{ Country::formatRate($country->super_reduced_rate) }}%</span>
                                @endif
                            </div>
                        </td>
                        <td class="py-1.5">
                            <div class="flex items-center gap-3">
                                <span class="tabular w-12 text-base font-bold text-ink">{{ Country::formatRate($country->standard_rate) }}%</span>
                                <span class="hidden h-1.5 w-20 overflow-hidden rounded-xs bg-action/15 sm:block" aria-hidden="true">
                                    <span class="block h-full bg-action" style="width: {{ round((float) $country->standard_rate / $maxRate * 100, 1) }}%"></span>
                                </span>
                            </div>
                        </td>
                        <td class="hidden py-1.5 sm:table-cell">
                            <div class="flex flex-wrap items-center gap-1.5">
                                @forelse($country->reducedRates() as $rate)
                                    <span class="tabular rounded-control bg-surface-muted px-2 py-0.5 text-xs font-semibold text-ink-muted">{{ Country::formatRate($rate) }}%</span>
                                @empty
                                    <span class="text-ink-quiet">—</span>
                                @endforelse
                                @if((float) $country->super_reduced_rate > 0)
                                    <span class="tabular rounded-control border border-dashed border-line-strong px-2 py-0.5 text-xs font-medium text-ink-quiet" title="{{ __('ui.rate_type.super_reduced') }}"><span class="sr-only">{{ __('ui.rate_type.super_reduced') }}: </span>{{ Country::formatRate($country->super_reduced_rate) }}%</span>
                                @endif
                            </div>
                        </td>
                        <td class="py-1.5 pr-5 text-right sm:pr-6">
                            <a href="{{ locale_path('/vat-calculator/'.$country->slug) }}" class="inline-flex size-9 items-center justify-center rounded-control text-ink-quiet transition-colors hover:bg-action-soft hover:text-action" aria-label="{{ __('ui.home_page.calculate_for', ['country' => $country->name]) }}">
                                <x-ui.icon name="arrow-right" class="size-4" />
                            </a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div role="status">
        <p x-cloak x-show="noMatches" class="px-6 py-10 text-center text-sm text-ink-muted">
            {{ __('ui.no_results') }}
        </p>
    </div>

    <div class="flex flex-wrap items-center justify-between gap-2 border-t border-line bg-surface-subtle px-5 py-3 text-xs text-ink-muted sm:px-6">
        <span class="hidden items-center gap-1.5 sm:inline-flex">
            <span class="inline-block h-1.5 w-4 rounded-xs bg-action" aria-hidden="true"></span>
            {{ __('ui.home_page.meter_legend', ['max' => Country::formatRate($maxRate)]) }}
        </span>
        <span class="inline-flex items-center gap-1.5">
            <span class="inline-block rounded-control border border-dashed border-line-strong px-1" aria-hidden="true">%</span>
            {{ __('ui.rate_type.super_reduced') }}
        </span>
    </div>
</section>
