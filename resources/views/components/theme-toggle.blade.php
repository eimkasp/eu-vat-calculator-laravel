@props(['withLabel' => false])

@php
    $labels = [
        'system' => __('ui.theme.system'),
        'light' => __('ui.theme.light'),
        'dark' => __('ui.theme.dark'),
    ];
@endphp

<button
    type="button"
    x-data="{ labels: @js($labels) }"
    @click="$store.theme.cycle()"
    :title="@js(__('ui.theme.label')) + ': ' + labels[$store.theme.mode]"
    :aria-label="@js(__('ui.theme.toggle')) + ' (' + labels[$store.theme.mode] + ')'"
    {{ $attributes->merge(['class' => 'lp-nav-item lp-fluid justify-center gap-2 text-sm font-semibold text-ink-muted hover:text-ink '.($withLabel ? 'inline-flex' : 'min-w-10')]) }}
>
    <x-ui.icon name="monitor" class="size-[1.125rem]" x-show="$store.theme.mode === 'system'" />
    <x-ui.icon name="sun" class="size-[1.125rem]" x-show="$store.theme.mode === 'light'" x-cloak />
    <x-ui.icon name="moon" class="size-[1.125rem]" x-show="$store.theme.mode === 'dark'" x-cloak />
    @if($withLabel)
        <span x-text="labels[$store.theme.mode]">{{ $labels['system'] }}</span>
    @endif
</button>
