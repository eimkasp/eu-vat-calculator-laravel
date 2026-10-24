@use('App\Support\SiteNavigation')

@php
    $tools = SiteNavigation::tools();
    $toolsActive = SiteNavigation::isActive('tools', 'vat-map', 'widget.*', 'vat-validation-api', 'vat-dataset', 'blog.*', 'vat-comparison*');
    $historyActive = SiteNavigation::isActive('vat-changes*', 'vat-rates.country-history');
    $primary = [
        ['label' => __('ui.nav.all_countries'), 'url' => locale_path('/'), 'active' => SiteNavigation::isActive('home')],
        ['label' => __('ui.nav.vat_calculator'), 'url' => locale_path('/vat-calculator'), 'active' => SiteNavigation::isActive('vat-calculator*', 'shared-calculation', 'top-calculations*')],
        ['label' => __('ui.nav.validator_short'), 'url' => locale_path('/vat-number-validator'), 'active' => SiteNavigation::isActive('vies-validator*')],
    ];
@endphp

<header
    x-data="{ menu: false, shown: false, toggle(open) { this.menu = open; this.shown = false; if (open) requestAnimationFrame(() => requestAnimationFrame(() => { this.shown = true })) } }"
    x-trap.inert.noscroll="menu"
    @keydown.escape.window="if (menu) { toggle(false); $refs.burger.focus() }"
    @resize.window.debounce.150ms="if (menu && innerWidth >= 1280) toggle(false)"
    class="pointer-events-none sticky top-0 z-40 px-4 pt-4 sm:pt-6"
>
    {{-- Skill B7 fluid island nav: a floating glass pill detached from the top of the page. --}}
    <div class="lp-island pointer-events-auto relative z-20 mx-auto flex w-max max-w-full items-center gap-1 p-2 text-ink">
        <a href="{{ locale_path('/') }}" class="lp-nav-item flex min-w-0 items-center gap-2 whitespace-nowrap text-base font-semibold text-ink">
            <x-ui.logo class="size-7" />
            <span class="truncate">{{ __('ui.site_name') }}</span>
        </a>

        <nav class="hidden shrink-0 items-center gap-1 xl:flex" aria-label="{{ __('ui.nav.primary') }}">
            @foreach($primary as $item)
                <a href="{{ $item['url'] }}" class="lp-nav-item lp-fluid text-sm font-semibold" @if($item['active']) aria-current="page" @endif>{{ $item['label'] }}</a>
            @endforeach

            <div class="relative flex" x-data="{ open: false }" @click.outside="open = false" @keydown.escape="if (open) { open = false; $refs.trigger.focus(); }" @focusout="if (! $el.contains($event.relatedTarget)) open = false">
                <button type="button" x-ref="trigger" @click="open = !open" aria-expanded="false" :aria-expanded="open.toString()" aria-controls="tools-menu" class="lp-nav-item lp-fluid flex items-center gap-1 text-sm font-semibold" @if($toolsActive) aria-current="true" @endif>
                    {{ __('ui.nav.vat_tools') }}
                    <x-ui.icon name="chevron-down" class="lp-fluid size-3.5" x-bind:class="open && 'rotate-180'" />
                </button>

                <div id="tools-menu" x-cloak x-show="open" x-transition:enter="transition duration-700 ease-fluid" x-transition:enter-start="-translate-y-2 opacity-0" x-transition:leave="transition duration-300 ease-fluid" x-transition:leave-end="opacity-0" class="app-popover absolute left-1/2 top-full z-50 mt-4 w-[26rem] -translate-x-1/2 p-2">
                    <div class="grid grid-cols-2 gap-1">
                        @foreach($tools as $tool)
                            <a href="{{ $tool['url'] }}" @class(['group flex gap-3 rounded-control p-3 transition-colors', 'bg-surface-muted' => $tool['active'], 'hover:bg-surface-muted' => ! $tool['active']]) @if($tool['active']) aria-current="page" @endif>
                                <span class="mt-0.5 flex size-8 shrink-0 items-center justify-center rounded-sm border border-line bg-surface-subtle text-ink">
                                    <x-ui.icon :name="$tool['icon']" class="size-4" />
                                </span>
                                <span class="min-w-0">
                                    <span class="block text-sm font-medium text-ink">{{ $tool['label'] }}</span>
                                    <span class="mt-0.5 block text-xs text-ink-muted">{{ $tool['description'] }}</span>
                                </span>
                            </a>
                        @endforeach
                    </div>
                    <a href="{{ locale_path('/tools') }}" class="mt-1 flex items-center justify-between border-t border-line px-3 pb-2 pt-3 text-sm font-semibold text-ink transition-colors hover:text-action">
                        {{ __('ui.nav.all_tools') }}
                        <x-ui.icon name="ph-arrow-right" class="size-4" />
                    </a>
                </div>
            </div>

            <a href="{{ locale_path('/vat-changes') }}" class="lp-nav-item lp-fluid text-sm font-semibold" @if($historyActive) aria-current="page" @endif>{{ __('ui.nav.vat_history') }}</a>
        </nav>

        <span class="mx-1 hidden h-6 w-px bg-line xl:block" aria-hidden="true"></span>

        <button type="button" @click="toggle(false); $nextTick(() => $store.palette.show())" class="lp-nav-item lp-fluid flex items-center gap-2 text-sm text-ink-muted hover:text-ink" aria-label="{{ __('ui.nav.search') }}">
            <x-ui.icon name="ph-magnifying-glass" class="size-5" />
            <kbd class="app-kbd hidden sm:inline-flex">⌘K</kbd>
        </button>
        <x-theme-toggle class="hidden sm:inline-flex" />
        <x-language-switcher class="hidden lg:block" />
        <a href="https://github.com/eimkasp/eu-vat.info" target="_blank" rel="noopener noreferrer" class="lp-nav-item lp-fluid hidden items-center text-ink-muted hover:text-ink 2xl:inline-flex" aria-label="{{ __('ui.nav.github') }} ({{ __('ui.opens_new_tab') }})">
            <x-ui.icon name="ph-github-logo" class="size-5" />
        </a>

        {{-- The two lines rotate and translate into an X; they never disappear. --}}
        <button type="button" x-ref="burger" @click="toggle(! menu)" class="lp-nav-item relative size-10 text-ink xl:hidden" aria-controls="mobile-navigation" aria-expanded="false" :aria-expanded="menu.toString()" aria-label="{{ __('ui.nav.toggle_menu') }}">
            <span class="lp-burger-line" aria-hidden="true"></span>
            <span class="lp-burger-line" aria-hidden="true"></span>
        </button>
    </div>

    {{-- The menu opens as a full-screen glass sheet; its links slide up from below, one after another. --}}
    <nav
        id="mobile-navigation"
        x-cloak
        x-show="menu"
        x-transition:enter="transition-opacity duration-700 ease-fluid"
        x-transition:enter-start="opacity-0"
        x-transition:leave="transition-opacity duration-300 ease-fluid"
        x-transition:leave-end="opacity-0"
        class="lp-sheet pointer-events-auto fixed inset-0 z-10 overflow-y-auto overscroll-contain px-6 pb-12 pt-24 xl:hidden"
        aria-label="{{ __('ui.nav.mobile') }}"
    >
        @php($sheetLinks = [...$primary, ['label' => __('ui.nav.vat_history'), 'url' => locale_path('/vat-changes'), 'active' => $historyActive]])
        <div class="mx-auto grid max-w-lg gap-2">
            @foreach($sheetLinks as $item)
                <a href="{{ $item['url'] }}" @class(['flex min-h-12 items-center text-3xl font-semibold tracking-tight transition-colors', 'text-ink underline decoration-2 underline-offset-8' => $item['active'], 'text-ink-muted hover:text-ink' => ! $item['active']]) @if($item['active']) aria-current="page" @endif>
                    <span class="lp-fluid block" :class="shown ? 'translate-y-0 opacity-100' : 'translate-y-12 opacity-0'" :style="shown ? 'transition-delay: {{ 100 + $loop->index * 50 }}ms' : ''">{{ $item['label'] }}</span>
                </a>
            @endforeach

            <p class="lp-fluid mt-8 text-sm font-medium text-ink-quiet" :class="shown ? 'translate-y-0 opacity-100' : 'translate-y-12 opacity-0'" :style="shown ? 'transition-delay: {{ 100 + count($sheetLinks) * 50 }}ms' : ''">{{ __('ui.nav.vat_tools') }}</p>
            <div class="grid grid-cols-1 gap-1 sm:grid-cols-2">
                @foreach($tools as $tool)
                    <a href="{{ $tool['url'] }}" @class(['flex min-h-12 items-center text-base font-medium transition-colors', 'text-ink underline decoration-2 underline-offset-8' => $tool['active'], 'text-ink-muted hover:text-ink' => ! $tool['active']]) @if($tool['active']) aria-current="page" @endif>
                        <span class="lp-fluid flex min-w-0 items-center gap-3" :class="shown ? 'translate-y-0 opacity-100' : 'translate-y-12 opacity-0'" :style="shown ? 'transition-delay: {{ 150 + (count($sheetLinks) + $loop->index) * 50 }}ms' : ''">
                            <x-ui.icon :name="$tool['icon']" class="size-4 text-ink-quiet" />
                            <span class="truncate">{{ $tool['label'] }}</span>
                        </span>
                    </a>
                @endforeach
            </div>

            <x-language-switcher :mobile="true" class="mt-8 border-t border-line pt-6">
                <x-theme-toggle :with-label="true" />
            </x-language-switcher>
        </div>
    </nav>
</header>

<x-announcement-bar />
