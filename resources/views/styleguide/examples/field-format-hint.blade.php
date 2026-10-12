<div class="max-w-xl">
    <label for="sg-format-number" class="mb-1.5 block text-[0.8125rem] font-semibold text-ink-muted">VAT number</label>
    <input
        id="sg-format-number"
        type="text"
        value="DE12345678"
        class="app-field font-mono"
        aria-describedby="sg-format-hint"
    >
    <p id="sg-format-hint" class="mt-3 flex items-start gap-2 text-sm font-medium text-warning" role="status">
        <x-ui.icon name="alert-triangle" class="mt-0.5 size-4 shrink-0" />
        <span>This does not look like a VAT number from Germany (DE999999999). Check for a typing mistake, or validate it anyway.</span>
    </p>
</div>
