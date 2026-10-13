<aside class="space-y-5" aria-label="{{ __('ui.home_page.sidebar_label') }}">
    <livewire:vat-rate-changes />

    <x-banner-display position="sidebar" />

    <livewire:recent-countries />

    <section class="app-surface overflow-hidden" aria-labelledby="map-card-heading">
        <div class="on-brand bg-brand px-5 pb-5 pt-6 text-white">
            <x-ui.icon name="map" class="size-6 text-gold" />
            <h2 id="map-card-heading" class="mt-3 text-base font-bold text-white">{{ __('ui.home_page.map_card_title') }}</h2>
            <p class="mt-1 text-sm leading-6 text-white/75">{{ __('ui.home_page.map_card_text') }}</p>
            <a href="{{ locale_path('/vat-map') }}" class="app-button-inverse mt-4 w-full">
                {{ __('ui.rate_changes.explore_map') }}
                <x-ui.icon name="arrow-right" class="size-4" />
            </a>
        </div>
    </section>

    <x-useful-vat-links />
</aside>
