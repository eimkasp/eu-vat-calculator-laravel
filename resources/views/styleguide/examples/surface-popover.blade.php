<div class="app-popover w-64 p-1.5">
    <p class="px-2.5 pb-1 pt-1.5 font-display text-[0.8125rem] font-medium text-ink-muted">Theme</p>
    @foreach([['monitor', 'System'], ['sun', 'Light'], ['moon', 'Dark']] as [$icon, $label])
        <button type="button" @class(['flex min-h-10 w-full items-center gap-2.5 rounded-control px-2.5 text-left text-sm', 'bg-action-soft font-semibold text-action-deep' => $loop->first, 'text-ink hover:bg-surface-muted' => ! $loop->first])>
            <x-ui.icon :name="$icon" class="size-4" />
            {{ $label }}
        </button>
    @endforeach
</div>
