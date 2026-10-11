@props(['faqs', 'title', 'id' => 'faq'])

{{-- Questions as native disclosures, first one open. Pair it with FAQPage structured data built from the same list. --}}
<section {{ $attributes->class(['app-surface overflow-hidden']) }} aria-labelledby="{{ $id }}">
    <h2 id="{{ $id }}" class="border-b border-line px-5 py-4 text-lg font-bold text-ink sm:px-6">{{ $title }}</h2>
    <div class="divide-y divide-line">
        @foreach($faqs as $faq)
            <details class="group px-5 sm:px-6" @if($loop->first) open @endif>
                <summary class="flex min-h-14 cursor-pointer list-none items-center justify-between gap-4 py-4 font-semibold text-ink [&::-webkit-details-marker]:hidden">
                    {{ $faq['q'] }}
                    <x-ui.icon name="chevron-down" class="size-4 shrink-0 text-ink-quiet transition-transform group-open:rotate-180" />
                </summary>
                <p class="-mt-1 max-w-[72ch] pb-5 text-sm leading-6 text-ink-muted">{{ $faq['a'] }}</p>
            </details>
        @endforeach
    </div>
</section>
