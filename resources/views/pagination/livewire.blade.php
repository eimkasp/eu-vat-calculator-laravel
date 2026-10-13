@php
    $scrollTo ??= 'body';
    $target = $scrollTo === 'body' ? '$wire.$el' : "(\$el.closest('{$scrollTo}') || document.querySelector('{$scrollTo}'))";
    $scroll = $scrollTo !== false ? "((target) => { target.scrollIntoView(); target.setAttribute('tabindex', '-1'); target.style.outline = 'none'; target.focus({ preventScroll: true }); })({$target})" : '';
    $pageName = $paginator->getPageName();
    $step = 'pressable inline-flex h-10 items-center gap-1.5 rounded-control px-3.5 text-sm font-semibold';
@endphp

<div>
    @if($paginator->hasPages())
        <nav aria-label="{{ __('ui.pagination.label') }}" class="flex items-center justify-between gap-3">
            @if($paginator->onFirstPage())
                <span aria-disabled="true" class="{{ $step }} cursor-not-allowed text-ink-quiet">
                    <x-ui.icon name="chevron-left" class="size-4" />
                    <span class="hidden sm:inline">{{ __('ui.pagination.previous') }}</span>
                </span>
            @else
                <button type="button" wire:click="previousPage('{{ $pageName }}')" x-on:click="{{ $scroll }}" wire:loading.attr="disabled" aria-label="{{ __('ui.pagination.previous') }}" class="{{ $step }} text-ink-muted hover:bg-surface-muted hover:text-ink">
                    <x-ui.icon name="chevron-left" class="size-4" />
                    <span class="hidden sm:inline">{{ __('ui.pagination.previous') }}</span>
                </button>
            @endif

            <ol class="hidden items-center gap-1 sm:flex">
                @foreach($elements as $element)
                    @if(is_string($element))
                        <li aria-hidden="true" class="px-1 text-sm text-ink-quiet">{{ $element }}</li>
                    @endif

                    @if(is_array($element))
                        @foreach($element as $page => $url)
                            <li wire:key="paginator-{{ $pageName }}-page-{{ $page }}">
                                @if($page == $paginator->currentPage())
                                    <span aria-current="page" class="tabular inline-flex size-10 items-center justify-center rounded-control border border-action bg-action-soft text-sm font-semibold text-action-deep">{{ $page }}</span>
                                @else
                                    <button type="button" wire:click="gotoPage({{ $page }}, '{{ $pageName }}')" x-on:click="{{ $scroll }}" aria-label="{{ __('ui.pagination.page', ['page' => $page]) }}" class="pressable tabular inline-flex size-10 items-center justify-center rounded-control text-sm font-semibold text-ink-muted hover:bg-surface-muted hover:text-ink">{{ $page }}</button>
                                @endif
                            </li>
                        @endforeach
                    @endif
                @endforeach
            </ol>

            <p class="tabular text-sm font-medium text-ink-muted sm:hidden">{{ $paginator->currentPage() }} / {{ $paginator->lastPage() }}</p>

            @if($paginator->hasMorePages())
                <button type="button" wire:click="nextPage('{{ $pageName }}')" x-on:click="{{ $scroll }}" wire:loading.attr="disabled" aria-label="{{ __('ui.pagination.next') }}" class="{{ $step }} text-ink-muted hover:bg-surface-muted hover:text-ink">
                    <span class="hidden sm:inline">{{ __('ui.pagination.next') }}</span>
                    <x-ui.icon name="chevron-right" class="size-4" />
                </button>
            @else
                <span aria-disabled="true" class="{{ $step }} cursor-not-allowed text-ink-quiet">
                    <span class="hidden sm:inline">{{ __('ui.pagination.next') }}</span>
                    <x-ui.icon name="chevron-right" class="size-4" />
                </span>
            @endif
        </nav>
    @endif
</div>
