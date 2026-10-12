<div x-data class="pointer-events-none fixed inset-x-0 bottom-24 z-[80] flex flex-col items-center gap-2 px-4 md:bottom-6 md:items-end md:px-6" aria-live="polite" aria-atomic="false">
    <template x-for="toast in $store.toasts.items" :key="toast.id">
        <div class="app-popover pointer-events-auto flex max-w-sm animate-pop-in items-center gap-2.5 py-2 pl-2.5 pr-2 text-sm font-medium">
            <span class="flex size-6 items-center justify-center rounded-control" :class="toast.tone === 'success' ? 'bg-success-soft text-success' : 'bg-action-soft text-action'">
                <x-ui.icon name="check" class="size-3.5" x-show="toast.tone === 'success'" />
                <x-ui.icon name="info" class="size-3.5" x-show="toast.tone !== 'success'" />
            </span>
            <span class="min-w-0" :class="toast.sticky && 'break-all select-all'" x-text="toast.message"></span>
            <button type="button" @click="$store.toasts.dismiss(toast.id)" class="ml-1 inline-flex size-7 items-center justify-center rounded-control text-ink-quiet hover:bg-surface-muted hover:text-ink" aria-label="{{ __('ui.close') }}">
                <x-ui.icon name="x" class="size-3.5" />
            </button>
        </div>
    </template>
</div>
