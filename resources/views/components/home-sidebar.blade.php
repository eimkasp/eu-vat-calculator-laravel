<aside class="space-y-5" aria-label="{{ __('ui.home_page.sidebar_label') }}">
    <livewire:vat-rate-changes />

    <x-banner-display position="sidebar" />

    <livewire:recent-countries />

    @php($comparisons = collect(\App\Support\SiteNavigation::comparisons())->take(5))
    @if($comparisons->isNotEmpty())
        <section class="app-surface p-5" aria-labelledby="compare-card-heading">
            <div class="flex items-baseline justify-between gap-3">
                <h2 id="compare-card-heading" class="text-base font-semibold text-ink">{{ __('ui.compare_index.popular') }}</h2>
                <a href="{{ locale_path('/compare') }}" class="app-link text-sm">{{ __('ui.compare_index.all') }}</a>
            </div>
            <ul class="mt-3 divide-y divide-line">
                @foreach($comparisons as $comparison)
                    <li>
                        <a href="{{ $comparison['url'] }}" class="group flex min-h-11 items-center gap-3 py-2">
                            <span class="flex shrink-0 -space-x-1.5" aria-hidden="true">
                                <x-ui.flag :iso="$comparison['left']->iso_code" size="xs" />
                                <x-ui.flag :iso="$comparison['right']->iso_code" size="xs" />
                            </span>
                            <span class="min-w-0 flex-1 truncate text-sm font-medium text-ink underline decoration-transparent underline-offset-4 transition-colors group-hover:decoration-action">{{ $comparison['label'] }}</span>
                            <span class="tabular shrink-0 text-xs text-ink-muted">{{ \App\Models\Country::formatRate($comparison['left']->standard_rate) }}% / {{ \App\Models\Country::formatRate($comparison['right']->standard_rate) }}%</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    <section class="app-surface p-5" aria-labelledby="map-card-heading">
        <div>
            <x-ui.icon name="map" class="size-6 text-action" />
            <h2 id="map-card-heading" class="mt-3 text-base font-semibold text-ink">{{ __('ui.home_page.map_card_title') }}</h2>
            <p class="mt-1 text-sm leading-6 text-ink-muted">{{ __('ui.home_page.map_card_text') }}</p>
            <a href="{{ locale_path('/vat-map') }}" class="app-button-secondary mt-4 w-full">
                {{ __('ui.rate_changes.explore_map') }}
                <x-ui.icon name="arrow-right" class="size-4" />
            </a>
        </div>
    </section>

    <x-useful-vat-links />
</aside>
