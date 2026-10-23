<div class="mx-auto max-w-xl overflow-hidden rounded-card border border-line" aria-hidden="true">
    <div class="flex h-7 items-center gap-2 border-b border-line bg-workspace px-3">
        <span class="size-3 rounded-xs bg-ink"></span>
        <span class="h-1.5 w-12 rounded-xs bg-ink/60"></span>
        <span class="ml-auto h-1.5 w-16 rounded-xs bg-line-strong"></span>
    </div>
    <div class="border-b border-line bg-workspace px-4 pb-5 pt-3">
        <span class="block h-1.5 w-24 rounded-xs bg-line-strong"></span>
        <span class="mt-4 block h-3.5 w-48 rounded-xs bg-ink/85"></span>
        <span class="mt-2 block h-2 w-64 max-w-full rounded-xs bg-line-strong"></span>
    </div>
    <div class="space-y-2 bg-workspace p-4">
        <div class="grid grid-cols-4 gap-2">
            @foreach(range(1, 4) as $tile)
                <span class="block h-9 rounded-xs border border-line bg-surface"></span>
            @endforeach
        </div>
        <div class="overflow-hidden rounded-xs border border-line bg-surface">
            <span class="block h-3 bg-surface-subtle"></span>
            @foreach(range(1, 3) as $row)
                <span class="block h-4 border-t border-line"></span>
            @endforeach
        </div>
    </div>
</div>
