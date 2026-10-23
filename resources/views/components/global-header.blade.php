@use('App\Support\SiteNavigation')

@php
    $tools = SiteNavigation::tools();
    $toolsActive = SiteNavigation::isActive('tools', 'vat-map', 'widget.*', 'vat-validation-api', 'vat-dataset', 'blog.*');
    $historyActive = SiteNavigation::isActive('vat-changes*', 'vat-rates.country-history');
    $primary = [
        ['label' => __('ui.nav.all_countries'), 'url' => locale_path('/'), 'active' => SiteNavigation::isActive('home')],
        ['label' => __('ui.nav.vat_calculator'), 'url' => locale_path('/vat-calculator'), 'active' => SiteNavigation::isActive('vat-calculator*', 'shared-calculation', 'top-calculations*')],
        ['label' => __('ui.nav.validator_short'), 'url' => locale_path('/vat-number-validator'), 'active' => SiteNavigation::isActive('vies-validator*')],
    ];
    $mobileActive = 'border-action bg-action-soft text-action-deep';
    $mobileIdle = 'border-transparent text-ink-muted hover:bg-surface-muted hover:text-ink';
@endphp

<header x-data="{ menu: false }" @keydown.escape.window="menu = false" class="sticky top-0 z-40 border-b border-line bg-workspace text-ink">
    <div class="app-container flex h-16 items-center gap-2">
        <a href="{{ locale_path('/') }}" class="-ml-1 flex min-w-0 items-center gap-2.5 whitespace-nowrap rounded-control px-1 py-1 font-display text-[1.0625rem] font-semibold tracking-[-0.02em] text-ink">
            <x-ui.logo />
            <span class="truncate">{{ __('ui.site_name') }}</span>
        </a>

        <nav class="ml-4 hidden shrink-0 items-center self-stretch xl:flex 2xl:ml-6" aria-label="{{ __('ui.nav.primary') }}">
            @foreach($primary as $item)
                <a href="{{ $item['url'] }}" @class(['app-nav-link', 'app-nav-link-active' => $item['active']]) @if($item['active']) aria-current="page" @endif>{{ $item['label'] }}</a>
            @endforeach

            <div class="relative flex" x-data="{ open: false }" @click.outside="open = false" @keydown.escape="if (open) { open = false; $refs.trigger.focus(); }" @focusout="if (! $el.contains($event.relatedTarget)) open = false">
                <button type="button" x-ref="trigger" @click="open = !open" aria-expanded="false" :aria-expanded="open.toString()" aria-controls="tools-menu" @class(['app-nav-link', 'app-nav-link-active' => $toolsActive])>
                    {{ __('ui.nav.vat_tools') }}
                    <x-ui.icon name="chevron-down" class="size-3.5 transition-transform duration-150" x-bind:class="open && 'rotate-180'" />
                </button>

                <div id="tools-menu" x-cloak x-show="open" x-transition:enter="transition duration-150 ease-out-quint" x-transition:enter-start="-translate-y-1 opacity-0" x-transition:leave="transition duration-100 ease-out" x-transition:leave-end="opacity-0" class="app-popover absolute left-1/2 top-full z-50 mt-px w-[26rem] -translate-x-1/2 p-2">
                    <div class="grid grid-cols-2 gap-1">
                        @foreach($tools as $tool)
                            <a href="{{ $tool['url'] }}" @class(['group flex gap-3 rounded-control p-3 transition-colors', 'bg-action-soft' => $tool['active'], 'hover:bg-surface-muted' => ! $tool['active']]) @if($tool['active']) aria-current="page" @endif>
                                <span class="mt-0.5 flex size-8 shrink-0 items-center justify-center rounded-control border border-line bg-surface-subtle text-action">
                                    <x-ui.icon :name="$tool['icon']" class="size-4" />
                                </span>
                                <span class="min-w-0">
                                    <span class="block font-display text-sm font-medium text-ink">{{ $tool['label'] }}</span>
                                    <span class="mt-0.5 block text-xs leading-5 text-ink-muted">{{ $tool['description'] }}</span>
                                </span>
                            </a>
                        @endforeach
                    </div>
                    <a href="{{ locale_path('/tools') }}" class="mt-1 flex items-center justify-between border-t border-line px-3 pb-1.5 pt-3 text-sm font-semibold text-action transition-colors hover:text-action-deep">
                        {{ __('ui.nav.all_tools') }}
                        <x-ui.icon name="arrow-right" class="size-4" />
                    </a>
                </div>
            </div>

            <a href="{{ locale_path('/vat-changes') }}" @class(['app-nav-link', 'app-nav-link-active' => $historyActive]) @if($historyActive) aria-current="page" @endif>{{ __('ui.nav.vat_history') }}</a>
        </nav>

        <div class="@container ml-auto flex min-w-[10.5rem] flex-1 items-center justify-end gap-1">
            <button type="button" @click="$store.palette.show()" class="pressable cut-sm hidden h-10 w-72 items-center gap-2.5 rounded-control border border-line-strong bg-surface px-3 text-left text-sm text-ink-muted hover:border-ink-quiet hover:text-ink @min-[27rem]:flex" aria-label="{{ __('ui.nav.search') }}">
                <x-ui.icon name="search" class="size-4" />
                <span class="flex-1 truncate">{{ __('ui.nav.search_placeholder') }}</span>
                <kbd class="app-kbd px-1.5">⌘K</kbd>
            </button>
            <button type="button" @click="$store.palette.show()" class="inline-flex size-10 items-center justify-center rounded-control text-ink-muted transition-colors hover:bg-surface-muted hover:text-ink @min-[27rem]:hidden" aria-label="{{ __('ui.nav.search') }}">
                <x-ui.icon name="search" class="size-5" />
            </button>

            <x-theme-toggle class="hidden sm:inline-flex" />
            <x-language-switcher class="hidden lg:block" />

            <a href="https://github.com/eimkasp/eu-vat.info" target="_blank" rel="noopener noreferrer" class="hidden size-10 items-center justify-center rounded-control text-ink-muted transition-colors hover:bg-surface-muted hover:text-ink @min-[30rem]:inline-flex" aria-label="{{ __('ui.nav.github') }} ({{ __('ui.opens_new_tab') }})">
                <x-ui.icon name="github" class="size-5" />
            </a>

            <button type="button" @click="menu = !menu" class="inline-flex size-10 items-center justify-center rounded-control text-ink transition-colors hover:bg-surface-muted xl:hidden" aria-controls="mobile-navigation" :aria-expanded="menu.toString()" aria-label="{{ __('ui.nav.toggle_menu') }}">
                <x-ui.icon name="menu" class="size-6" x-show="!menu" />
                <x-ui.icon name="x" class="size-6" x-show="menu" x-cloak />
            </button>
        </div>
    </div>

    <nav id="mobile-navigation" x-cloak x-show="menu" x-transition.opacity.duration.150ms class="max-h-[calc(100dvh-4rem)] overflow-y-auto overscroll-contain border-t border-line pb-4 xl:hidden" aria-label="{{ __('ui.nav.mobile') }}">
        <div class="app-container grid gap-1 pt-3">
            @foreach($primary as $item)
                <a href="{{ $item['url'] }}" @class(['flex min-h-11 items-center border-l-2 px-3 text-[0.9375rem] font-medium', $mobileActive => $item['active'], $mobileIdle => ! $item['active']]) @if($item['active']) aria-current="page" @endif>{{ $item['label'] }}</a>
            @endforeach
            <a href="{{ locale_path('/vat-changes') }}" @class(['flex min-h-11 items-center border-l-2 px-3 text-[0.9375rem] font-medium', $mobileActive => $historyActive, $mobileIdle => ! $historyActive]) @if($historyActive) aria-current="page" @endif>{{ __('ui.nav.vat_history') }}</a>

            <p class="mt-4 px-3 font-display text-[0.8125rem] font-medium text-ink-quiet">{{ __('ui.nav.vat_tools') }}</p>
            <div class="grid grid-cols-2 gap-1">
                @foreach($tools as $tool)
                    <a href="{{ $tool['url'] }}" @class(['flex min-h-11 items-center gap-2.5 border-l-2 px-3 text-sm font-medium', $mobileActive => $tool['active'], $mobileIdle => ! $tool['active']]) @if($tool['active']) aria-current="page" @endif>
                        <x-ui.icon :name="$tool['icon']" class="size-4 text-ink-quiet" />
                        <span class="truncate">{{ $tool['label'] }}</span>
                    </a>
                @endforeach
            </div>

            <x-language-switcher :mobile="true" class="mt-3 border-t border-line px-1 pt-4">
                <x-theme-toggle :with-label="true" />
            </x-language-switcher>
        </div>
    </nav>
</header>

<x-announcement-bar />
