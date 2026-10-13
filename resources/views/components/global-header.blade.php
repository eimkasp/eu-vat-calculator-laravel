@use('App\Support\SiteNavigation')

@php
    $tools = SiteNavigation::tools();
    $toolsActive = SiteNavigation::isActive('tools', 'vat-map', 'vat-changes*', 'widget.*', 'vat-validation-api', 'vat-dataset');
    $primary = [
        ['label' => __('ui.nav.all_countries'), 'url' => locale_path('/'), 'active' => SiteNavigation::isActive('home')],
        ['label' => __('ui.nav.vat_calculator'), 'url' => locale_path('/vat-calculator'), 'active' => SiteNavigation::isActive('vat-calculator*', 'shared-calculation', 'top-calculations*')],
        ['label' => __('ui.nav.validator_short'), 'url' => locale_path('/vat-number-validator'), 'active' => SiteNavigation::isActive('vies-validator*')],
    ];
    $mobileActive = 'border-gold bg-white/10 text-white';
    $mobileIdle = 'border-transparent text-white/85 hover:bg-white/10 hover:text-white';
@endphp

<header x-data="{ menu: false }" @keydown.escape.window="menu = false" class="on-brand sticky top-0 z-40 border-b border-white/10 bg-brand-deep text-white">
    <div class="app-container flex h-16 items-center gap-2">
        <a href="{{ locale_path('/') }}" class="-ml-1 flex min-w-0 items-center gap-2.5 whitespace-nowrap rounded-control px-1 py-1 text-[1.0625rem] font-bold tracking-[-0.02em] text-white">
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
                                    <span class="block text-sm font-semibold text-ink">{{ $tool['label'] }}</span>
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

            <a href="{{ locale_path('/blog') }}" @class(['app-nav-link', 'app-nav-link-active' => SiteNavigation::isActive('blog.*')]) @if(SiteNavigation::isActive('blog.*')) aria-current="page" @endif>{{ __('ui.nav.updates') }}</a>
        </nav>

        <div class="@container ml-auto flex min-w-[10.5rem] flex-1 items-center justify-end gap-1">
            <button type="button" @click="$store.palette.show()" class="app-brand-panel pressable hidden h-10 w-72 items-center gap-2.5 rounded-control px-3 text-left text-sm text-white/80 hover:border-white/30 hover:bg-white/10 hover:text-white @min-[27rem]:flex" aria-label="{{ __('ui.nav.search') }}">
                <x-ui.icon name="search" class="size-4" />
                <span class="flex-1 truncate">{{ __('ui.nav.search_placeholder') }}</span>
                <kbd class="rounded-xs border border-white/25 px-1.5 py-0.5 font-sans text-[0.6875rem] font-semibold text-white/80">⌘K</kbd>
            </button>
            <button type="button" @click="$store.palette.show()" class="inline-flex size-10 items-center justify-center rounded-control text-white/85 transition-colors hover:bg-white/10 hover:text-white @min-[27rem]:hidden" aria-label="{{ __('ui.nav.search') }}">
                <x-ui.icon name="search" class="size-5" />
            </button>

            <x-theme-toggle class="hidden sm:inline-flex" />
            <x-language-switcher class="hidden lg:block" />

            <a href="https://github.com/eimkasp/eu-vat.info" target="_blank" rel="noopener noreferrer" class="hidden size-10 items-center justify-center rounded-control text-white/80 transition-colors hover:bg-white/10 hover:text-white @min-[30rem]:inline-flex" aria-label="{{ __('ui.nav.github') }} ({{ __('ui.opens_new_tab') }})">
                <x-ui.icon name="github" class="size-5" />
            </a>

            <button type="button" @click="menu = !menu" class="inline-flex size-10 items-center justify-center rounded-control text-white transition-colors hover:bg-white/10 xl:hidden" aria-controls="mobile-navigation" :aria-expanded="menu.toString()" aria-label="{{ __('ui.nav.toggle_menu') }}">
                <x-ui.icon name="menu" class="size-6" x-show="!menu" />
                <x-ui.icon name="x" class="size-6" x-show="menu" x-cloak />
            </button>
        </div>
    </div>

    <nav id="mobile-navigation" x-cloak x-show="menu" x-transition.opacity.duration.150ms class="max-h-[calc(100dvh-4rem)] overflow-y-auto overscroll-contain border-t border-white/10 pb-4 xl:hidden" aria-label="{{ __('ui.nav.mobile') }}">
        <div class="app-container grid gap-1 pt-3">
            @foreach($primary as $item)
                <a href="{{ $item['url'] }}" @class(['flex min-h-11 items-center border-l-2 px-3 text-[0.9375rem] font-medium', $mobileActive => $item['active'], $mobileIdle => ! $item['active']]) @if($item['active']) aria-current="page" @endif>{{ $item['label'] }}</a>
            @endforeach
            <a href="{{ locale_path('/blog') }}" @class(['flex min-h-11 items-center border-l-2 px-3 text-[0.9375rem] font-medium', $mobileActive => SiteNavigation::isActive('blog.*'), $mobileIdle => ! SiteNavigation::isActive('blog.*')]) @if(SiteNavigation::isActive('blog.*')) aria-current="page" @endif>{{ __('ui.nav.updates') }}</a>

            <p class="mt-4 px-3 text-xs font-semibold tracking-[0.08em] text-white/60 uppercase">{{ __('ui.nav.vat_tools') }}</p>
            <div class="grid grid-cols-2 gap-1">
                @foreach($tools as $tool)
                    <a href="{{ $tool['url'] }}" @class(['flex min-h-11 items-center gap-2.5 border-l-2 px-3 text-sm font-medium', $mobileActive => $tool['active'], $mobileIdle => ! $tool['active']]) @if($tool['active']) aria-current="page" @endif>
                        <x-ui.icon :name="$tool['icon']" class="size-4 text-white/70" />
                        <span class="truncate">{{ $tool['label'] }}</span>
                    </a>
                @endforeach
            </div>

            <x-language-switcher :mobile="true" class="mt-3 border-t border-white/10 px-1 pt-4">
                <x-theme-toggle :with-label="true" />
            </x-language-switcher>
        </div>
    </nav>
</header>

<x-announcement-bar />
