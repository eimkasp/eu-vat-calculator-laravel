@props(['mobile' => false])

@php
    $locales = supported_locales();
    $current = app()->getLocale();
    $currentConfig = current_locale_config();
@endphp

@if($mobile)
    <div x-data="{ open: false }" {{ $attributes->merge(['class' => 'flex flex-wrap items-center justify-between gap-3']) }}>
        <button
            type="button"
            @click="open = !open"
            :aria-expanded="open.toString()"
            aria-expanded="false"
            aria-controls="mobile-language-menu"
            class="app-brand-panel inline-flex h-10 items-center gap-2 rounded-control px-3 text-sm font-medium"
        >
            <x-ui.icon name="languages" class="size-4 text-white/70" />
            <span class="sr-only">{{ __('ui.language_switcher.label') }}:</span>
            {{ $currentConfig['native'] ?? 'English' }}
            <x-ui.icon name="chevron-down" class="size-3.5 transition-transform duration-150" x-bind:class="open && 'rotate-180'" />
        </button>
        {{ $slot }}
        {{-- Built when opened from the desktop menu's links, which every page already carries. --}}
        <template x-if="open">
            <ul id="mobile-language-menu" class="grid basis-full grid-cols-2 gap-1">
                <template x-for="link in [...document.querySelectorAll('#language-menu a')]" :key="link.lang">
                    <li>
                        <a :href="link.getAttribute('href')" :lang="link.lang" :aria-current="link.getAttribute('aria-current')" class="flex min-h-11 items-center gap-2 border-l-2 px-3 text-sm font-medium" :class="link.hasAttribute('aria-current') ? 'border-gold bg-white/10 text-white' : 'border-transparent text-white/85 hover:bg-white/10 hover:text-white'">
                            <img :src="link.querySelector('img')?.getAttribute('src')" alt="" width="18" height="14" loading="lazy" decoding="async" class="app-flag h-3.5 w-[1.125rem]">
                            <span class="truncate" x-text="link.textContent.trim()"></span>
                        </a>
                    </li>
                </template>
            </ul>
        </template>
    </div>
@else
    <div x-data="{ open: false }" @click.outside="open = false" @keydown.escape="if (open) { open = false; $refs.trigger.focus(); }" @focusout="if (! $el.contains($event.relatedTarget)) open = false" {{ $attributes->merge(['class' => 'relative']) }}>
        <button
            type="button"
            x-ref="trigger"
            @click="open = !open"
            :aria-expanded="open.toString()"
            aria-expanded="false"
            aria-controls="language-menu"
            aria-label="{{ __('ui.language_switcher.current', ['name' => $currentConfig['native'] ?? 'English']) }}"
            class="inline-flex h-10 items-center gap-1.5 rounded-control px-3 text-sm font-semibold uppercase text-white/85 transition-colors hover:bg-white/10 hover:text-white"
        >
            <x-ui.flag :iso="$currentConfig['flag'] ?? 'gb'" size="sm" :lazy="false" class="shadow-none ring-1 ring-white/25" />
            {{ $current }}
            <x-ui.icon name="chevron-down" class="size-3.5 transition-transform duration-150" x-bind:class="open && 'rotate-180'" />
        </button>

        <div id="language-menu" x-cloak x-show="open" x-transition:enter="transition duration-150 ease-out-quint" x-transition:enter-start="-translate-y-1 opacity-0" x-transition:leave="transition duration-100 ease-out" x-transition:leave-end="opacity-0" class="app-popover absolute right-0 top-full z-50 mt-3 grid max-h-[70vh] w-72 grid-cols-2 gap-0.5 overflow-y-auto p-1.5">
            @foreach($locales as $code => $config)
                <a href="{{ locale_switch_path($code) }}" lang="{{ $code }}" @class(['flex items-center gap-2 rounded-control px-2.5 py-2 text-sm transition-colors', 'bg-action-soft font-semibold text-action-deep' => $code === $current, 'text-ink-muted hover:bg-surface-muted hover:text-ink' => $code !== $current]) @if($code === $current) aria-current="true" @endif>
                    <x-ui.flag :iso="$config['flag']" size="sm" />
                    <span class="truncate">{{ $config['native'] }}</span>
                </a>
            @endforeach
        </div>
    </div>
@endif
