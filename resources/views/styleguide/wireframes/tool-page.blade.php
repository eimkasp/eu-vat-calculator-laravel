<div class="mx-auto max-w-xl overflow-hidden rounded-card border border-line" aria-hidden="true">
    <div class="flex h-7 items-center gap-2 border-b border-line bg-workspace px-3">
        <span class="size-3 rounded-xs bg-ink"></span>
        <span class="h-1.5 w-12 rounded-xs bg-ink/60"></span>
        <span class="ml-auto h-1.5 w-16 rounded-xs bg-line-strong"></span>
    </div>
    <div class="relative isolate overflow-clip border-b border-line bg-workspace px-4 pb-6 pt-3">
        <div class="absolute inset-y-0 right-0 -z-10 flex w-1/2 items-end gap-1 px-3 opacity-60">
            @foreach([40, 55, 48, 70, 62, 85, 52, 66, 78, 58, 90, 64] as $height)
                <span class="block w-full rounded-xs bg-action/20" style="height: {{ $height }}%"></span>
            @endforeach
        </div>
        <span class="block h-1.5 w-24 rounded-xs bg-line-strong"></span>
        <span class="mt-4 block h-3.5 w-48 rounded-xs bg-ink/85"></span>
        <span class="mt-2 block h-2 w-40 rounded-xs bg-ink/30"></span>
        <div class="app-surface-raised mt-5 grid max-w-[85%] grid-cols-2">
            <div class="space-y-2 p-3">
                <span class="block h-5 w-full rounded-xs border border-line bg-surface-muted"></span>
                <span class="block h-5 w-full rounded-xs border border-ink-quiet/60"></span>
                <span class="block h-5 w-2/3 rounded-xs border border-ink-quiet/60"></span>
            </div>
            <div class="space-y-2 border-l border-line bg-surface-subtle p-3">
                <span class="block h-4 w-2/3 rounded-xs bg-ink/80"></span>
                <span class="block h-2 w-full rounded-xs bg-line-strong"></span>
                <span class="block h-5 w-full rounded-xs bg-button"></span>
            </div>
        </div>
    </div>
    <div class="grid grid-cols-3 gap-2 bg-workspace p-4">
        <span class="col-span-2 block h-16 rounded-xs border border-line bg-surface"></span>
        <span class="block h-16 rounded-xs border border-line bg-surface"></span>
    </div>
</div>
