@use('App\Models\Country')
@use('App\Support\Seo\SeoPolicy')

@php
    $seo = app(SeoPolicy::class);
    $historyCanonical = $seo->canonicalHost().locale_path('/vat-changes');
    $historyRobots = request()->query() ? 'noindex, follow' : null;
    $summary = $this->summary;
    $stability = $this->stability;
    $maxChanges = max([1, ...array_column($stability, 'changes')]);
    $today = now()->startOfDay();
    $selectClass = 'app-select h-10 min-h-10 text-sm';
@endphp

@section('seo')
    <x-seo-meta :title="__('ui.history.meta_title')" :description="__('ui.history.meta_desc')" :url="$historyCanonical" :robots="$historyRobots">
        <x-json-ld :data="[
            '@type' => 'WebPage',
            'name' => __('ui.history.meta_title'),
            'description' => __('ui.history.meta_desc'),
            'url' => $historyCanonical,
            'inLanguage' => app()->getLocale(),
            'isPartOf' => ['@type' => 'WebSite', '@id' => $seo->canonicalHost().'/#website', 'name' => 'EU VAT Info', 'url' => $seo->canonicalHost().'/'],
        ]" />
        <x-json-ld :data="array_filter([
            '@type' => 'Dataset',
            'name' => 'EU VAT Rate Changes since '.($firstChangeYear ?? now()->year),
            'description' => __('ui.history.meta_desc'),
            'url' => $historyCanonical,
            'creator' => ['@type' => 'Organization', 'name' => __('ui.site_name'), 'url' => $seo->canonicalHost()],
            'dateModified' => $datasetModified?->toIso8601String(),
            'license' => 'https://creativecommons.org/licenses/by/4.0/',
            'keywords' => ['VAT rate changes', 'EU VAT history', 'European VAT rates', 'tax rate changes'],
            'spatialCoverage' => 'European Union',
            'temporalCoverage' => ($firstChangeYear ?? now()->year).'/..',
            'variableMeasured' => [
                ['@type' => 'PropertyValue', 'name' => 'Standard VAT Rate', 'unitText' => 'percent'],
                ['@type' => 'PropertyValue', 'name' => 'Reduced VAT Rate', 'unitText' => 'percent'],
            ],
        ])" />
    </x-seo-meta>
@endsection

<div>
    <x-page-header :title="__('ui.history.title')" :description="__('ui.history.subtitle')" :eyebrow="__('ui.nav.vat_tools')" :breadcrumbs="[__('ui.breadcrumbs.vat_changelog') => '']">
        @if($summary['latest'])
            <x-slot:actions>
                <span class="inline-flex items-center gap-2 rounded-control border border-line bg-surface-subtle px-3 py-1.5 text-sm text-ink-muted">
                    <x-ui.icon name="clock" class="size-4" />
                    {{ __('ui.history.latest_change', ['date' => \Illuminate\Support\Carbon::parse($summary['latest'])->translatedFormat('j M Y')]) }}
                </span>
            </x-slot:actions>
        @endif
    </x-page-header>

    <div class="app-container space-y-8 py-8 sm:py-10">
        <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
            @foreach([
                ['history', __('ui.history.stat_total'), $summary['total'], 'text-action'],
                ['trending-up', __('ui.history.stat_increases'), $summary['increases'], 'text-danger'],
                ['trending-down', __('ui.history.stat_decreases'), $summary['decreases'], 'text-success'],
                ['clock', __('ui.history.stat_upcoming'), $summary['upcoming'], 'text-warning'],
            ] as [$icon, $label, $value, $tone])
                <div class="app-surface flex items-start gap-3 p-4">
                    <span class="flex size-9 shrink-0 items-center justify-center rounded-control bg-surface-muted {{ $tone }}" aria-hidden="true">
                        <x-ui.icon :name="$icon" class="size-4" />
                    </span>
                    <dl>
                        <dt class="text-xs font-semibold text-ink-muted">{{ $label }}</dt>
                        <dd class="tabular mt-0.5 text-2xl font-bold text-ink">{{ number_format($value) }}</dd>
                    </dl>
                </div>
            @endforeach
        </div>

        <div class="grid grid-cols-1 items-start gap-8 xl:grid-cols-[minmax(0,1fr)_22rem]">
            <section class="app-surface min-w-0 overflow-clip" aria-labelledby="changes-heading">
                <div class="border-b border-line p-4 sm:px-6">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <h2 id="changes-heading" class="text-lg font-bold text-ink">{{ __('ui.history.all_changes') }}</h2>
                        <p class="flex items-center gap-2 text-sm text-ink-muted" aria-live="polite">
                            <span wire:loading class="size-3.5 animate-spin rounded-full border-2 border-action/30 border-t-action" aria-hidden="true"></span>
                            {{ trans_choice('ui.history.results_count', $changes->total(), ['count' => number_format($changes->total())]) }}
                        </p>
                    </div>

                    <div role="group" aria-label="{{ __('ui.history.filters_label') }}" @class(['mt-4 grid grid-cols-2 gap-3 sm:items-end', 'sm:grid-cols-[repeat(3,minmax(0,1fr))_auto]' => count($this->rateTypes) > 1, 'sm:grid-cols-[repeat(2,minmax(0,1fr))_auto]' => count($this->rateTypes) <= 1])>
                        <div class="col-span-2 sm:col-span-1">
                            <label for="filter-country" class="mb-1 block text-xs font-semibold text-ink-muted">{{ __('ui.country') }}</label>
                            <select id="filter-country" wire:model.live="selectedCountry" class="{{ $selectClass }}">
                                <option value="">{{ __('ui.history.all_countries') }}</option>
                                @foreach($this->countries as $option)
                                    <option value="{{ $option['slug'] }}">{{ $option['name'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        @if(count($this->rateTypes) > 1)
                            <div>
                                <label for="filter-type" class="mb-1 block text-xs font-semibold text-ink-muted">{{ __('ui.history.rate_type') }}</label>
                                <select id="filter-type" wire:model.live="selectedType" class="{{ $selectClass }}">
                                    <option value="">{{ __('ui.history.all_types') }}</option>
                                    @foreach($this->rateTypes as $type)
                                        <option value="{{ $type }}">{{ __('ui.rate_type.'.$type) }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                        <div>
                            <label for="filter-direction" class="mb-1 block text-xs font-semibold text-ink-muted">{{ __('ui.history.direction') }}</label>
                            <select id="filter-direction" wire:model.live="selectedDirection" class="{{ $selectClass }}">
                                <option value="">{{ __('ui.history.all_directions') }}</option>
                                <option value="increase">{{ __('ui.history.increases') }}</option>
                                <option value="decrease">{{ __('ui.history.decreases') }}</option>
                            </select>
                        </div>
                        @if($hasFilters)
                            <button type="button" wire:click="resetFilters" class="app-button-ghost h-10 min-h-10 px-3">
                                <x-ui.icon name="x" class="size-4" />
                                {{ __('ui.history.clear_filters') }}
                            </button>
                        @endif
                    </div>
                </div>

                <div wire:loading.class="opacity-60" class="transition-opacity">
                    @forelse($changes->groupBy(fn ($change) => $change->change_date?->year) as $year => $yearChanges)
                        <h3 class="app-sticky-bar tabular px-4 py-2 text-xs font-bold tracking-[0.08em] text-ink-muted sm:px-6"><a href="{{ locale_path('/vat-changes/year/'.$year) }}" class="inline-flex min-h-6 items-center hover:text-action">{{ $year }}</a></h3>
                        <ol class="divide-y divide-line">
                            @foreach($yearChanges as $change)
                                @php
                                    $delta = (float) $change->new_rate - (float) $change->old_rate;
                                    $up = $delta > 0;
                                    $upcoming = $change->change_date?->gt($today);
                                @endphp
                                <li wire:key="change-{{ $change->id }}" class="flex flex-wrap items-center gap-x-4 gap-y-2 px-4 py-3.5 sm:flex-nowrap sm:px-6">
                                    <time datetime="{{ $change->change_date?->toDateString() }}" class="tabular w-24 shrink-0 text-sm text-ink-muted">{{ $change->change_date?->translatedFormat('j M Y') }}</time>

                                    <div class="flex min-w-0 flex-1 items-center gap-3">
                                        <x-ui.flag :iso="$change->country?->iso_code" size="lg" class="h-5 w-[1.625rem]" />
                                        <div class="min-w-0">
                                            <p class="flex flex-wrap items-center gap-2">
                                                <span class="truncate font-semibold text-ink">{{ $change->country?->name }}</span>
                                                @if($change->rate_type !== 'standard')
                                                    <span class="rounded-control bg-surface-muted px-1.5 py-0.5 text-[0.6875rem] font-semibold text-ink-muted">{{ __('ui.rate_type.'.$change->rate_type) }}</span>
                                                @endif
                                                @if($upcoming)
                                                    <span class="inline-flex items-center gap-1 rounded-control bg-warning-soft px-1.5 py-0.5 text-[0.6875rem] font-semibold text-warning">
                                                        <x-ui.icon name="clock" class="size-3" />
                                                        {{ __('ui.history.upcoming_badge') }}
                                                    </span>
                                                @endif
                                            </p>
                                            @if($change->editorialDescription())
                                                <p class="mt-0.5 line-clamp-1 text-xs text-ink-muted">{{ $change->editorialDescription() }}</p>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="flex shrink-0 items-center gap-3">
                                        <span class="tabular text-sm text-ink-muted">
                                            {{ Country::formatRate($change->old_rate) }}%
                                            <x-ui.icon name="arrow-right" class="inline size-3.5 align-[-2px] text-ink-quiet" />
                                            <span class="font-bold text-ink">{{ Country::formatRate($change->new_rate) }}%</span>
                                        </span>
                                        @if($delta != 0)
                                            <span @class(['tabular inline-flex min-w-16 items-center justify-end gap-0.5 text-xs font-semibold', 'text-danger' => $up, 'text-success' => ! $up])>
                                                <x-ui.icon :name="$up ? 'trending-up' : 'trending-down'" class="size-3.5" />
                                                <span class="sr-only">{{ $up ? __('ui.rate_changes.increase') : __('ui.rate_changes.decrease') }}</span>
                                                {{ __('ui.rate_changes.points', ['value' => ($up ? '+' : '−').Country::formatRate(abs($delta))]) }}
                                            </span>
                                        @else
                                            <span class="min-w-16"></span>
                                        @endif
                                        <a href="{{ locale_path('/vat-calculator/'.$change->country?->slug) }}" class="app-button-ghost size-9 min-h-9 p-0" aria-label="{{ __('ui.history.view_calculator') }}: {{ $change->country?->name }}">
                                            <x-ui.icon name="calculator" class="size-4" />
                                        </a>
                                    </div>
                                </li>
                            @endforeach
                        </ol>
                    @empty
                        <div class="px-6 py-14 text-center">
                            <span class="mx-auto flex size-12 items-center justify-center rounded-full bg-surface-muted text-ink-quiet" aria-hidden="true">
                                <x-ui.icon name="search" class="size-5" />
                            </span>
                            <p class="mt-4 text-sm text-ink-muted">{{ __('ui.history.no_changes') }}</p>
                            @if($hasFilters)
                                <button type="button" wire:click="resetFilters" class="app-button-secondary mt-4">{{ __('ui.history.clear_filters') }}</button>
                            @endif
                        </div>
                    @endforelse
                </div>

                @if($changes->hasPages())
                    <div class="border-t border-line px-4 py-3 sm:px-6">
                        {{ $changes->links() }}
                    </div>
                @endif
            </section>

            <aside class="space-y-6 xl:sticky xl:top-24">
                @livewire('vat-change-signup', ['source' => 'vat-changes', 'compact' => true])

                <section class="app-surface p-5" aria-labelledby="stability-heading">
                    <h2 id="stability-heading" class="text-base font-bold text-ink">{{ __('ui.history.stability_title') }}</h2>
                    <p class="mt-1 text-sm leading-6 text-ink-muted">{{ __('ui.history.stability_desc') }}</p>
                    <div class="mt-4 flex items-center justify-between border-b border-line pb-2 text-xs font-semibold text-ink-muted">
                        <span>{{ __('ui.history.col_country') }}</span>
                        <span>{{ __('ui.history.col_changes') }}</span>
                    </div>
                    <ol class="mt-1 max-h-[34rem] space-y-0.5 overflow-y-auto overscroll-contain">
                        @foreach($stability as $row)
                            <li>
                                <a href="{{ locale_path($row['history'] ? '/vat-rates/'.$row['slug'].'/history' : '/vat-calculator/'.$row['slug']) }}" class="group -mx-2 grid grid-cols-[1.125rem_minmax(0,7.5rem)_minmax(0,1fr)_1.75rem] items-center gap-2.5 rounded-control px-2 py-1.5 transition-colors hover:bg-surface-subtle" title="{{ __('ui.history.col_stability') }}: {{ __('ui.history.'.$row['stability']) }}">
                                    <x-ui.flag :iso="$row['iso']" size="sm" />
                                    <span class="truncate text-[0.8125rem] font-medium text-ink group-hover:text-action">{{ $row['name'] }}</span>
                                    <span class="h-1.5 overflow-hidden rounded-xs bg-action-soft" aria-hidden="true">
                                        <span class="block h-full bg-action" style="width: {{ max(4, round($row['changes'] / $maxChanges * 100)) }}%"></span>
                                    </span>
                                    <span class="tabular text-right text-[0.8125rem] font-semibold text-ink">{{ $row['changes'] }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ol>
                    <p class="mt-3 text-xs text-ink-muted">{{ __('ui.history.stability_scale') }}</p>
                </section>
            </aside>
        </div>
    </div>
</div>
