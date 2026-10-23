@use('App\Support\DesignSystem\CodeHighlighter')
@use('App\Support\DesignSystem\ComponentCatalog')

@props(['title' => null, 'example' => null, 'preview' => null, 'code' => null, 'description' => null, 'canvas' => null, 'language' => 'Blade'])

@php
    $entry = $example ? ComponentCatalog::example($example) : [];
    $title ??= $entry['title'] ?? '';
    $description ??= $entry['description'] ?? null;
    $canvas ??= $entry['canvas'] ?? 'surface';
    $preview ??= $example ? 'styleguide.examples.'.$example : null;
    $source = trim($code ?? file_get_contents(resource_path('views/styleguide/examples/'.$example.'.blade.php')));
    $canvasClass = ComponentCatalog::CANVASES[$canvas] ?? ComponentCatalog::CANVASES['surface'];
@endphp

<figure {{ $attributes->merge(['class' => 'app-surface flex flex-col overflow-hidden']) }} x-data>
    <figcaption class="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1 border-b border-line px-5 py-3">
        <h3 class="font-display text-[0.9375rem] font-semibold text-ink">{{ $title }}</h3>
        @if($description)
            <p class="text-xs leading-5 text-ink-muted">{{ $description }}</p>
        @endif
    </figcaption>

    @if($preview)
        <div class="{{ $canvasClass }} flex flex-1 flex-col justify-center p-5 sm:p-6">
            <div>
                @include($preview)
            </div>
        </div>
    @endif

    <details class="group border-line {{ $preview ? 'border-t' : 'flex-1' }}" @unless($preview) open @endunless>
        <summary class="flex min-h-10 cursor-pointer list-none items-center justify-between gap-3 px-5 font-display text-xs font-medium text-ink-muted transition-colors hover:text-ink [&::-webkit-details-marker]:hidden">
            <span class="inline-flex items-center gap-1.5">
                <x-ui.icon name="code" class="size-3.5" />
                {{ $language }}
            </span>
            <x-ui.icon name="chevron-down" class="size-3.5 transition-transform duration-150 group-open:rotate-180" />
        </summary>
        <div class="bg-code">
            <div class="flex h-9 items-center justify-end border-b border-white/10 px-2">
                <button type="button" class="pressable inline-flex h-7 items-center gap-1.5 rounded-control px-2.5 font-display text-xs font-medium text-white/80 hover:bg-white/10 hover:text-white" x-on:click="$copy($refs.code.textContent, 'Code copied')">
                    <x-ui.icon name="copy" class="size-3.5" />
                    Copy
                </button>
            </div>
            <pre tabindex="0" class="max-h-96 overflow-auto p-4 font-mono text-xs leading-5 text-white/85"><code x-ref="code">{!! CodeHighlighter::blade($source, [
                'comment' => 'text-syntax-comment',
                'attribute' => 'text-syntax-function',
                'blade' => 'text-syntax-literal',
                'tag' => 'text-syntax-keyword',
                'string' => 'text-syntax-string',
            ]) !!}</code></pre>
        </div>
    </details>
</figure>
