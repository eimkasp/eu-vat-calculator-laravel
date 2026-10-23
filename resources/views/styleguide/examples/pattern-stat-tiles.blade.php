<div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
    @foreach([
        ['history', 'Recorded changes', '140', 'text-action'],
        ['trending-up', 'Increases', '111', 'text-danger'],
        ['trending-down', 'Decreases', '29', 'text-success'],
        ['clock', 'Scheduled', '2', 'text-warning'],
    ] as [$icon, $label, $value, $tone])
        <div class="app-surface flex items-start gap-3 p-4">
            <span class="flex size-9 shrink-0 items-center justify-center rounded-control bg-surface-muted {{ $tone }}" aria-hidden="true">
                <x-ui.icon :name="$icon" class="size-4" />
            </span>
            <dl>
                <dt class="font-display text-[0.8125rem] font-medium text-ink-muted">{{ $label }}</dt>
                <dd class="tabular mt-0.5 font-display text-2xl font-semibold text-ink">{{ $value }}</dd>
            </dl>
        </div>
    @endforeach
</div>
