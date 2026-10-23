@props(['countries', 'active' => null, 'headingLevel' => 'h2'])

@use('App\Models\Country')
@use('App\Support\EuropeMapSvg')

@php
    $headingId = 'europe-map-title';
    $data = collect($countries)->mapWithKeys(fn (Country $country) => [strtoupper((string) $country->iso_code) => [
        'code' => strtoupper((string) $country->iso_code),
        'iso' => strtolower((string) $country->iso_code),
        'name' => $country->name,
        'rate' => Country::formatRate($country->standard_rate).'%',
        'reduced' => $country->formattedReducedRates(', '),
        'url' => locale_path('/vat-calculator/'.$country->slug),
        'validator' => $country->vies_available ? locale_path('/vat-number-validator/'.$country->slug) : null,
    ]])->all();
@endphp

<div x-data="europeMap(@js($data), @js($active ? strtoupper($active) : null))" {{ $attributes->merge(['class' => 'relative']) }}>
    <div class="flex flex-wrap items-center justify-between gap-x-6 gap-y-3">
        <{{ $headingLevel }} id="{{ $headingId }}" class="text-lg font-bold text-ink">{{ __('ui.map.heading') }}</{{ $headingLevel }}>
        <ul class="flex flex-wrap items-center gap-x-3 gap-y-1.5 text-xs font-medium text-ink-muted" aria-label="{{ __('ui.map.legend') }}">
            @foreach(EuropeMapSvg::bucketLabels() as $index => $label)
                <li class="inline-flex items-center gap-1.5"><span class="eu-map-swatch eu-map-swatch-{{ $index }}" aria-hidden="true"></span>{{ $label }}</li>
            @endforeach
            <li class="inline-flex items-center gap-1.5"><span class="eu-map-swatch eu-map-swatch-none" aria-hidden="true"></span>{{ __('ui.map.no_data') }}</li>
        </ul>
    </div>

    <div
        x-ref="canvas"
        class="relative mt-4 overflow-hidden rounded-card border border-line bg-surface"
        @mouseover="hoverRegion($event)"
        @mousemove="track($event)"
        @mouseleave="hover(null)"
        @focusin="focusRegion($event)"
        @focusout="hover(null)"
        @click="selectRegion($event)"
        @keydown.enter.prevent="selectRegion($event)"
        @keydown.space.prevent="selectRegion($event)"
    >
        {!! EuropeMapSvg::render(collect($countries), $headingId) !!}

        <div
            x-cloak
            x-show="hovered"
            class="pointer-events-none absolute z-10 flex items-center gap-2 rounded-control border border-line bg-surface px-3 py-2 text-sm shadow-floating"
            :style="`left:${Math.min(Math.max(x + 14, 8), $refs.canvas.clientWidth - 220)}px; top:${Math.max(y - 48, 8)}px`"
        >
            <template x-if="hovered">
                <span class="flex items-center gap-2">
                    <img :src="'{{ asset('images/flags') }}/' + hovered.iso + '.svg'" alt="" width="22" height="16" class="app-flag h-4 w-[1.375rem]">
                    <span class="font-semibold text-ink" x-text="hovered.name"></span>
                    <span class="font-bold text-action-deep" x-text="hovered.rate"></span>
                </span>
            </template>
        </div>
    </div>

    <p class="mt-2 text-xs text-ink-muted">{{ __('ui.map.interaction_hint') }}</p>

    <div aria-live="polite">
        <div x-cloak x-show="selected" x-transition.opacity.duration.150ms class="mt-4 rounded-card border border-line bg-surface p-4 sm:p-5">
            <template x-if="selected">
                <div class="flex flex-wrap items-center gap-4">
                    <img :src="'{{ asset('images/flags') }}/' + selected.iso + '.svg'" alt="" width="48" height="36" class="app-flag h-9 w-12 rounded-control">
                    <div class="min-w-0 flex-1">
                        <p class="text-lg font-bold text-ink" x-text="selected.name"></p>
                        <p class="flex flex-wrap gap-x-4 text-sm text-ink-muted">
                            <span>{{ __('ui.rate_type.standard') }} <strong class="tabular text-ink" x-text="selected.rate"></strong></span>
                            <span x-show="selected.reduced">{{ __('ui.rate_type.reduced') }} <strong class="tabular text-ink" x-text="selected.reduced"></strong></span>
                        </p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <a :href="selected.url" class="app-button-primary h-10 min-h-10 px-4">
                            <x-ui.icon name="calculator" class="size-4" />
                            {{ __('ui.nav.vat_calculator') }}
                        </a>
                        <a x-show="selected.validator" :href="selected.validator" class="app-button-secondary h-10 min-h-10 px-4">
                            <x-ui.icon name="shield-check" class="size-4" />
                            {{ __('ui.nav.validator_short') }}
                        </a>
                        <button type="button" @click="clear()" class="app-button-ghost size-10 min-h-10 px-0" aria-label="{{ __('ui.map.clear_selection') }}">
                            <x-ui.icon name="x" class="size-4" />
                        </button>
                    </div>
                </div>
            </template>
        </div>
    </div>
</div>
