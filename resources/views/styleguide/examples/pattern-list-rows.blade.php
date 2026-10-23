<ol class="app-surface divide-y divide-line overflow-hidden">
    @foreach([
        ['2025-08-01', 'RO', 'Romania', 19, 21],
        ['2025-07-01', 'EE', 'Estonia', 22, 24],
        ['2023-01-01', 'LU', 'Luxembourg', 17, 16],
    ] as [$date, $iso, $country, $old, $new])
        <li class="flex flex-wrap items-center gap-x-4 gap-y-2 px-4 py-3.5 sm:flex-nowrap sm:px-5">
            <time datetime="{{ $date }}" class="tabular w-24 shrink-0 text-sm text-ink-muted">{{ \Illuminate\Support\Carbon::parse($date)->format('j M Y') }}</time>
            <div class="flex min-w-0 flex-1 items-center gap-3">
                <x-ui.flag :iso="$iso" size="lg" class="h-5 w-[1.625rem]" />
                <p class="flex min-w-0 flex-wrap items-center gap-2">
                    <span class="truncate font-semibold text-ink">{{ $country }}</span>
                    <span class="app-badge bg-surface-muted text-ink-muted">Standard</span>
                </p>
            </div>
            <div class="flex shrink-0 items-center gap-3">
                <span class="tabular text-sm text-ink-muted">
                    {{ $old }}%
                    <x-ui.icon name="arrow-right" class="inline size-3.5 align-[-2px] text-ink-quiet" />
                    <span class="font-semibold text-ink">{{ $new }}%</span>
                </span>
                <span @class(['tabular inline-flex min-w-16 items-center justify-end gap-0.5 text-xs font-semibold', 'text-danger' => $new > $old, 'text-success' => $new < $old])>
                    <x-ui.icon :name="$new > $old ? 'trending-up' : 'trending-down'" class="size-3.5" />
                    <span class="sr-only">{{ $new > $old ? 'Increase' : 'Decrease' }}</span>
                    {{ $new > $old ? '+' : '−' }}{{ abs($new - $old) }} pp
                </span>
            </div>
        </li>
    @endforeach
</ol>
