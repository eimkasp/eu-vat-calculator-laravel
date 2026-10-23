{{-- On a page, write <x-hero-backdrop /> as the first child of hero-field; it reads the live rates. This specimen passes fixed rates so it renders without the database. --}}
<div class="hero-field rounded-card border border-line">
    <div class="hero-ground" aria-hidden="true" x-data="rateField(@js(\App\Support\DesignSystem\ComponentCatalog::SAMPLE_RATES))"></div>
    <div class="relative px-5 py-10 sm:px-8 sm:py-14">
        <h4 class="max-w-sm text-3xl font-semibold tracking-[-0.03em] text-ink">EU VAT calculator</h4>
        <p class="mt-2 max-w-[40ch] text-sm leading-6 text-ink-muted">Raw cells on the left resolve into one column per member state on the right, each as tall as its standard rate. Keep headings on the left half.</p>
    </div>
</div>
