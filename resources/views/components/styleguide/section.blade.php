@props(['id', 'title', 'lead' => null])

<section id="{{ $id }}" class="scroll-mt-24" aria-labelledby="{{ $id }}-title">
    <header class="max-w-[68ch] border-b border-line pb-4">
        <h2 id="{{ $id }}-title" class="text-2xl font-semibold tracking-[-0.02em] text-ink">
            <a href="#{{ $id }}" class="group inline-flex items-center gap-2 rounded-control">
                {{ $title }}
                <x-ui.icon name="hash" class="size-4 text-ink-quiet opacity-0 transition-opacity group-hover:opacity-100 group-focus-visible:opacity-100" />
            </a>
        </h2>
        @if($lead)
            <p class="mt-2 text-base leading-7 text-ink-muted">{{ $lead }}</p>
        @endif
    </header>
    <div class="mt-6 space-y-6">
        {{ $slot }}
    </div>
</section>
