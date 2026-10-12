@props(['items', 'variant' => 'light', 'schema' => null])

@php
    $dark = $variant === 'dark';
@endphp

<nav {{ $attributes->merge(['class' => 'text-sm', 'aria-label' => __('ui.breadcrumbs.label')]) }}>
    <ol class="flex flex-wrap items-center gap-x-1.5 gap-y-1">
        <li class="inline-flex items-center">
            <a href="{{ locale_path('/') }}" @class(['inline-flex min-h-6 items-center gap-1.5 rounded-control transition-colors', 'text-white/70 hover:text-white' => $dark, 'text-ink-muted hover:text-action' => ! $dark])>
                <x-ui.icon name="home" class="size-3.5" />
                <span>{{ __('ui.breadcrumbs.home') }}</span>
            </a>
        </li>
        @foreach($items as $label => $url)
            <li class="inline-flex items-center gap-1.5">
                <x-ui.icon name="chevron-right" :class="$dark ? 'size-3.5 text-white/40' : 'size-3.5 text-ink-quiet'" />
                @if(! $loop->last && $url)
                    <a href="{{ $url }}" @class(['inline-flex min-h-6 items-center rounded-control font-medium transition-colors', 'text-white/70 hover:text-white' => $dark, 'text-ink-muted hover:text-action' => ! $dark])>{{ $label }}</a>
                @else
                    <span aria-current="page" @class(['font-medium', 'text-white' => $dark, 'text-ink' => ! $dark])>{{ $label }}</span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
@if($schema)
    <x-json-ld :data="$schema" />
@endif
