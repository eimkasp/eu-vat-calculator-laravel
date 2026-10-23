{{-- €1,250.00 plus 19% VAT: the VAT is 16% of the total, so 8 of the 50 cells are filled. --}}
<div class="max-w-md">
    <p class="font-display text-[0.8125rem] font-medium text-ink-muted">Total (incl. VAT)</p>
    <p class="tabular mt-1 font-display text-4xl font-semibold tracking-[-0.03em] text-ink">€1,487.50</p>
    <div class="app-meter mt-4" aria-hidden="true">
        <p class="app-meter-cells"><span class="text-ink-quiet">{{ str_repeat('░', 42) }}</span><span class="text-action">{{ str_repeat('█', 8) }}</span></p>
        <div class="mt-2 flex justify-between font-display text-xs text-ink-muted">
            <span>Net 84%</span>
            <span>VAT 16%</span>
        </div>
    </div>
</div>
