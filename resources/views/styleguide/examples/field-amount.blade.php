<div class="grid max-w-xl grid-cols-1 gap-4 sm:grid-cols-2">
    <div>
        <label for="sg-amount" class="mb-1.5 block font-display text-[0.8125rem] font-medium text-ink-muted">Amount (excl. VAT)</label>
        <div class="relative">
            <span class="pointer-events-none absolute inset-y-0 left-3.5 flex items-center font-semibold text-ink-quiet" aria-hidden="true">€</span>
            <input id="sg-amount" type="text" inputmode="decimal" value="1,250.00" class="app-field tabular h-12 pl-10 text-lg font-semibold">
        </div>
    </div>
    <div>
        <label for="sg-search" class="mb-1.5 block font-display text-[0.8125rem] font-medium text-ink-muted">Country</label>
        <div class="relative">
            <x-ui.icon name="search" class="pointer-events-none absolute left-3.5 top-1/2 size-4 -translate-y-1/2 text-ink-quiet" />
            <input id="sg-search" type="search" placeholder="Search countries…" class="app-field h-12 pl-10">
        </div>
    </div>
</div>
