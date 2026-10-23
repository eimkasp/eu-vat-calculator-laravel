<div class="max-w-md space-y-5">
    <div>
        <div class="flex h-1.5 overflow-hidden rounded-xs bg-action/15" aria-hidden="true">
            <div class="h-full w-[84%] bg-action"></div>
        </div>
        <p class="mt-1.5 flex justify-between font-display text-xs text-ink-muted">
            <span>Net 84%</span>
            <span>VAT 16%</span>
        </p>
    </div>
    <ul class="flex flex-wrap items-center gap-x-4 gap-y-1.5 text-xs text-ink-muted" aria-label="Map legend">
        @foreach(['< 20%', '20%', '21–22%', '23–24%', '≥ 25%'] as $step => $label)
            <li class="inline-flex items-center gap-1.5">
                <span class="eu-map-swatch eu-map-swatch-{{ $step }}" aria-hidden="true"></span>
                {{ $label }}
            </li>
        @endforeach
        <li class="inline-flex items-center gap-1.5">
            <span class="eu-map-swatch eu-map-swatch-none" aria-hidden="true"></span>
            No data
        </li>
    </ul>
</div>
