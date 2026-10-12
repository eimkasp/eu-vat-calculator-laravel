@props(['items', 'variant' => 'light'])

@php
    $dark = $variant === 'dark';
@endphp

<nav {{ $attributes->merge(['class' => 'text-sm', 'aria-label' => __('ui.breadcrumbs.label')]) }}>
    <ol class="flex flex-wrap items-center gap-x-1.5 gap-y-1" itemscope itemtype="https://schema.org/BreadcrumbList">
        <li class="inline-flex items-center" itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
            <a href="{{ locale_path('/') }}" itemprop="item" @class(['inline-flex items-center gap-1.5 rounded-control transition-colors', 'text-white/70 hover:text-white' => $dark, 'text-ink-muted hover:text-action' => ! $dark])>
                <x-ui.icon name="home" class="size-3.5" />
                <span itemprop="name">{{ __('ui.breadcrumbs.home') }}</span>
            </a>
            <meta itemprop="position" content="1">
        </li>
        @foreach($items as $label => $url)
            <li class="inline-flex items-center gap-1.5" itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
                <x-ui.icon name="chevron-right" :class="$dark ? 'size-3.5 text-white/40' : 'size-3.5 text-ink-quiet'" />
                @if(! $loop->last && $url)
                    <a href="{{ $url }}" itemprop="item" @class(['rounded-control font-medium transition-colors', 'text-white/70 hover:text-white' => $dark, 'text-ink-muted hover:text-action' => ! $dark])>
                        <span itemprop="name">{{ $label }}</span>
                    </a>
                @else
                    <span aria-current="page" itemprop="item" itemid="{{ request()->url() }}" @class(['font-medium', 'text-white' => $dark, 'text-ink' => ! $dark])>
                        <span itemprop="name">{{ $label }}</span>
                    </span>
                @endif
                <meta itemprop="position" content="{{ $loop->iteration + 1 }}">
            </li>
        @endforeach
    </ol>
</nav>
