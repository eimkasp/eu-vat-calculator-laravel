@php
    $flagBase = asset('images/flags');
@endphp

<div
    x-data="commandPalette({ source: @js(route('search-index', ['locale' => app()->getLocale()])) })"
    x-show="$store.palette.open"
    x-trap.noscroll="$store.palette.open"
    x-cloak
    @keydown.escape.window="$store.palette.hide()"
    class="fixed inset-0 z-[70] flex items-start justify-center px-3 pt-[12vh] sm:px-6"
    role="dialog"
    aria-modal="true"
    aria-labelledby="palette-title"
>
    <div class="scrim absolute inset-0" @click="$store.palette.hide()" x-show="$store.palette.open" x-transition.opacity.duration.150ms></div>

    <div class="app-popover relative w-full max-w-xl overflow-hidden rounded-panel" x-show="$store.palette.open" x-transition:enter="transition duration-150 ease-out-quint" x-transition:enter-start="translate-y-1 opacity-0" x-transition:enter-end="translate-y-0 opacity-100">
        <h2 id="palette-title" class="sr-only">{{ __('ui.palette.title') }}</h2>
        <div class="flex items-center gap-3 border-b border-line px-4">
            <x-ui.icon name="search" class="size-5 text-ink-quiet" />
            <input
                x-ref="input"
                x-model="query"
                @keydown.arrow-down.prevent="move(1)"
                @keydown.arrow-up.prevent="move(-1)"
                @keydown.enter.prevent="go()"
                type="text"
                role="combobox"
                aria-expanded="true"
                aria-controls="palette-results"
                :aria-activedescendant="results.length ? 'palette-option-' + active : null"
                autocomplete="off"
                spellcheck="false"
                placeholder="{{ __('ui.palette.placeholder') }}"
                aria-label="{{ __('ui.palette.placeholder') }}"
                class="h-14 w-full bg-transparent text-base text-ink outline-none placeholder:text-ink-quiet"
            >
            <kbd class="app-kbd hidden sm:inline-flex">Esc</kbd>
        </div>

        <ul id="palette-results" x-ref="list" role="listbox" class="max-h-[min(60vh,26rem)] overflow-y-auto overscroll-contain p-2">
            <template x-for="(item, index) in results" :key="item.url">
                <li
                    :id="'palette-option-' + index"
                    :data-index="index"
                    role="option"
                    :aria-selected="(index === active).toString()"
                    @mousemove="active = index"
                    @click="go(item)"
                    class="flex min-h-12 cursor-pointer items-center gap-3 rounded-control px-3 py-2"
                    :class="index === active ? 'bg-action-soft' : ''"
                >
                    <template x-if="item.flag">
                        <img :src="@js($flagBase) + '/' + item.flag + '.svg'" alt="" width="24" height="18" class="app-flag h-[1.125rem] w-6">
                    </template>
                    <template x-if="!item.flag">
                        <span class="flex size-6 items-center justify-center rounded-xs bg-surface-muted text-ink-muted">
                            <x-ui.icon name="arrow-right" class="size-3.5" />
                        </span>
                    </template>
                    <span class="min-w-0 flex-1">
                        <span class="block truncate text-sm font-semibold text-ink" x-text="item.title"></span>
                        <span class="block truncate text-xs text-ink-muted" x-text="item.subtitle"></span>
                    </span>
                    <span class="tabular shrink-0 text-sm font-semibold text-ink-muted" x-show="item.meta" x-text="item.meta"></span>
                </li>
            </template>
            <li x-show="loading && results.length === 0" class="flex items-center justify-center gap-2 px-3 py-10 text-sm text-ink-muted" role="presentation">
                <span class="size-4 animate-spin rounded-full border-2 border-action/30 border-t-action" aria-hidden="true"></span>
                {{ __('ui.loading') }}
            </li>
            <li x-show="!loading && results.length === 0" class="px-3 py-10 text-center text-sm text-ink-muted" role="presentation">
                {{ __('ui.palette.empty') }}
            </li>
        </ul>

        <div class="hidden items-center gap-4 border-t border-line bg-surface-subtle px-4 py-2.5 text-xs text-ink-muted sm:flex">
            <span class="inline-flex items-center gap-1.5"><kbd class="app-kbd">↑</kbd><kbd class="app-kbd">↓</kbd> {{ __('ui.palette.hint_navigate') }}</span>
            <span class="inline-flex items-center gap-1.5"><kbd class="app-kbd">↵</kbd> {{ __('ui.palette.hint_open') }}</span>
            <span class="ml-auto inline-flex items-center gap-1.5"><kbd class="app-kbd">⌘</kbd><kbd class="app-kbd">K</kbd> {{ __('ui.palette.hint_toggle') }}</span>
        </div>
    </div>
</div>
