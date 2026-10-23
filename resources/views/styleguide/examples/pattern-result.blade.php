<div class="grid max-w-3xl grid-cols-1 overflow-hidden rounded-card border border-line bg-surface md:grid-cols-2">
    <div class="p-5">
        <p class="font-display text-[0.8125rem] font-medium text-ink-muted">Total (incl. VAT)</p>
        <div class="mt-1 flex items-end justify-between gap-3">
            <p class="tabular font-display text-4xl font-semibold tracking-[-0.03em] text-ink">€1,487.50</p>
            <button type="button" class="app-button-secondary h-9 min-h-9 px-3 text-xs" aria-label="Copy the calculation">
                <x-ui.icon name="copy" class="size-3.5" />
                Copy
            </button>
        </div>
        <p class="mt-2 text-sm text-ink-muted">€1,250.00 plus 19% VAT in Germany</p>
    </div>
    <div class="border-t border-line bg-surface-subtle p-5 md:border-l md:border-t-0">
        <dl class="divide-y divide-line rounded-control border border-line bg-surface text-sm">
            <div class="flex items-center justify-between px-4 py-2.5">
                <dt class="text-ink-muted">Net amount</dt>
                <dd class="tabular font-semibold text-ink">€1,250.00</dd>
            </div>
            <div class="flex items-center justify-between px-4 py-2.5">
                <dt class="text-ink-muted">VAT 19%</dt>
                <dd class="tabular font-semibold text-action-deep">+ €237.50</dd>
            </div>
        </dl>
        <button type="button" class="app-button-primary mt-4 h-10 min-h-10 w-full px-4">
            <x-ui.icon name="share" class="size-4" />
            Share result
        </button>
    </div>
</div>
