@php
    $pageUrl = url('/brand');
    $canonical = rtrim((string) config('seo.canonical_url'), '/');
    $markUrl = url('/brand/eu-vat-info-mark.svg');
    $swatches = [
        'brand' => 'bg-brand',
        'brand-deep' => 'bg-brand-deep',
        'gold' => 'bg-gold',
        'action' => 'bg-action',
        'button' => 'bg-button',
        'ink' => 'bg-ink',
        'workspace' => 'bg-workspace',
        'surface' => 'bg-surface',
    ];
    $voice = [
        ['Say what is true and when', 'Rates updated 11 Oct 2026, from the European Commission', 'Always up to date'],
        ['Lead with the answer', 'Germany VAT is 19%. Reduced rates of 7% apply to selected goods.', 'Welcome to the ultimate German VAT resource'],
        ['Write in sentence case', 'Validate a VAT number', 'Validate A VAT Number'],
        ['Give reference data, not advice', 'VAT treatment depends on the goods and the customer.', 'You must charge 19% on this sale.'],
    ];
    $resources = [
        ['grid', 'Design system', 'Tokens, components and page patterns with live examples', url('/styleguide')],
        ['puzzle', 'Storybook', 'Every component and pattern as a story, in light and dark', url('/storybook/')],
        ['braces', 'Design Tokens', 'Colour, type, shape and motion as DTCG JSON', url('/styleguide/design.tokens.json')],
        ['file-text', 'DESIGN.md', 'The full guide as Markdown, for people and AI agents', url('/styleguide.md')],
    ];
@endphp

@section('seo')
    <x-seo-meta
        title="Brand guidelines: logo, colour and voice | EU VAT Info"
        description="The EU VAT Info brand: the mark and how to use it, the navy, gold and blue palette, Inter type, the plain voice and how to credit our VAT data."
        :url="$pageUrl"
        type="article">
        <x-json-ld :data="[
            '@type' => 'WebPage',
            'name' => 'EU VAT Info brand guidelines',
            'url' => $pageUrl,
            'inLanguage' => 'en',
            'isPartOf' => ['@type' => 'WebSite', 'name' => __('ui.site_name'), 'url' => url('/')],
            'about' => ['@type' => 'Organization', 'name' => __('ui.site_name'), 'url' => url('/'), 'logo' => $markUrl],
            'relatedLink' => [url('/styleguide'), url('/storybook/')],
        ]" />
        <x-json-ld :data="[
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                ['@type' => 'ListItem', 'position' => 1, 'name' => __('ui.site_name'), 'item' => url('/')],
                ['@type' => 'ListItem', 'position' => 2, 'name' => __('ui.brand.nav_label'), 'item' => $pageUrl],
            ],
        ]" />
    </x-seo-meta>
@endsection

<div>
    <section class="hero-canvas">
        <x-hero-backdrop />
        <div class="app-container relative pb-12 pt-6 sm:pb-16 sm:pt-8">
            <x-site-breadcrumbs :items="[__('ui.brand.nav_label') => '']" variant="dark" />

            <div class="mt-6 grid grid-cols-1 items-end gap-10 lg:grid-cols-[minmax(0,1fr)_auto]" lang="en">
                <div class="max-w-3xl">
                    <p class="app-kicker">Brand · Version {{ $version }}</p>
                    <h1 class="mt-3 text-4xl font-bold tracking-[-0.03em] text-white sm:text-5xl sm:leading-[1.08]">The EU VAT Info brand</h1>
                    <p class="mt-4 max-w-2xl text-base leading-7 text-white/85 sm:text-lg">How EU VAT Info looks and speaks: the mark, the colours, the type and the voice. Use them when you write about, link to or build with EU VAT Info.</p>
                    <div class="mt-8 flex flex-wrap gap-3">
                        <a href="{{ $markUrl }}" download="eu-vat-info-mark.svg" class="app-button-inverse">
                            <x-ui.icon name="download" class="size-4" />
                            Download the mark
                        </a>
                        <a href="{{ url('/styleguide') }}" class="app-button-on-brand">
                            <x-ui.icon name="grid" class="size-4" />
                            Open the design system
                        </a>
                        <a href="{{ url('/storybook/') }}" class="app-button-on-brand">
                            <x-ui.icon name="puzzle" class="size-4" />
                            Browse Storybook
                        </a>
                    </div>
                </div>
                <div class="app-brand-panel hidden items-center gap-4 p-6 lg:flex" aria-hidden="true">
                    <x-ui.logo class="size-16" />
                    <span class="text-2xl font-bold tracking-[-0.02em] text-white">{{ __('ui.site_name') }}</span>
                </div>
            </div>
        </div>
    </section>

    <div class="app-container space-y-16 py-10 sm:py-14" lang="en">
        @if(app()->getLocale() !== 'en')
            <p class="inline-flex items-center gap-2 text-sm text-ink-muted">
                <x-ui.icon name="languages" class="size-4" />
                {{ __('ui.brand.english_only') }}
            </p>
        @endif

        <section aria-labelledby="brand-mark" class="space-y-6">
            <header class="max-w-[68ch]">
                <h2 id="brand-mark" class="text-2xl font-bold tracking-[-0.02em] text-ink">The mark</h2>
                <p class="mt-2 text-base leading-7 text-ink-muted">A white tile with a navy percent sign and a gold dot: a calculation, and the gold of the European flag. It always sits on its own white tile, so it reads on navy and on white alike.</p>
            </header>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                <figure class="app-surface overflow-hidden">
                    <div class="hero-canvas flex h-40 items-center justify-center gap-3">
                        <x-ui.logo class="size-12" />
                        <span class="text-xl font-bold tracking-[-0.02em] text-white">{{ __('ui.site_name') }}</span>
                    </div>
                    <figcaption class="border-t border-line px-5 py-3 text-sm text-ink-muted">On navy, with the wordmark in white</figcaption>
                </figure>
                <figure class="app-surface overflow-hidden">
                    <div class="flex h-40 items-center justify-center gap-3 bg-surface">
                        <x-ui.logo class="size-12 rounded-card ring-1 ring-line" />
                        <span class="text-xl font-bold tracking-[-0.02em] text-ink">{{ __('ui.site_name') }}</span>
                    </div>
                    <figcaption class="border-t border-line px-5 py-3 text-sm text-ink-muted">On white, with a hairline edge and the wordmark in ink</figcaption>
                </figure>
                <figure class="app-surface overflow-hidden">
                    <div class="flex h-40 items-end justify-center gap-6 bg-workspace pb-10">
                        <x-ui.logo class="size-16" />
                        <x-ui.logo class="size-8" />
                        <x-ui.logo class="size-4" />
                    </div>
                    <figcaption class="border-t border-line px-5 py-3 text-sm text-ink-muted">64, 32 and 16 px; never smaller than 16 px</figcaption>
                </figure>
            </div>
            <div class="app-surface grid grid-cols-1 gap-6 p-5 sm:p-6 md:grid-cols-2">
                <div>
                    <h3 class="flex items-center gap-2 font-bold text-ink"><x-ui.icon name="check-circle" class="size-4 text-success" /> Do</h3>
                    <ul class="mt-3 space-y-2 text-sm leading-6 text-ink-muted">
                        <li>Keep clear space of half the tile's width on every side.</li>
                        <li>Use the SVG at any size from 16 px up.</li>
                        <li>Set the wordmark in Inter Bold, next to the tile.</li>
                    </ul>
                </div>
                <div>
                    <h3 class="flex items-center gap-2 font-bold text-ink"><x-ui.icon name="x-circle" class="size-4 text-danger" /> Don't</h3>
                    <ul class="mt-3 space-y-2 text-sm leading-6 text-ink-muted">
                        <li>Recolour, rotate, stretch or outline the mark.</li>
                        <li>Move the gold dot or change it to another colour.</li>
                        <li>Place it on photos or busy backgrounds without its tile.</li>
                    </ul>
                </div>
            </div>
        </section>

        <section aria-labelledby="brand-colour" class="space-y-6">
            <header class="max-w-[68ch]">
                <h2 id="brand-colour" class="text-2xl font-bold tracking-[-0.02em] text-ink">Colour</h2>
                <p class="mt-2 text-base leading-7 text-ink-muted">EU navy frames every page, blue marks what you can act on and gold is a thin signature on navy. Values come straight from the Design Tokens; the dark value is what the site shows in dark mode.</p>
            </header>
            <ul class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                @foreach($colours as $colour)
                    <li class="app-surface overflow-hidden">
                        <span class="{{ $swatches[$colour['name']] }} block h-20 border-b border-line" aria-hidden="true"></span>
                        <div class="p-4">
                            <p class="font-mono text-sm font-semibold text-ink">{{ $colour['name'] }}</p>
                            <p class="tabular mt-0.5 text-xs text-ink-muted">{{ $colour['hex'] }}@if($colour['dark']) · dark {{ $colour['dark'] }}@endif</p>
                            @if($colour['description'])
                                <p class="mt-2 text-xs leading-5 text-ink-quiet">{{ $colour['description'] }}</p>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ul>
            <p class="app-note max-w-3xl">
                <x-ui.icon name="info" class="mt-1 size-4 shrink-0 text-action" />
                <span>Gold only ever appears on navy, and white text only on the button blue, never on the lighter action blue.</span>
            </p>
        </section>

        <section aria-labelledby="brand-type" class="space-y-6">
            <header class="max-w-[68ch]">
                <h2 id="brand-type" class="text-2xl font-bold tracking-[-0.02em] text-ink">Type</h2>
                <p class="mt-2 text-base leading-7 text-ink-muted">Inter Variable, with its optical sizes, for everything. Headings stay one colour, and every figure that can change or be compared is set in tabular numbers so columns line up.</p>
            </header>
            <div class="app-surface divide-y divide-line">
                <div class="grid gap-2 p-5 sm:grid-cols-[10rem_minmax(0,1fr)] sm:items-baseline sm:p-6">
                    <p class="font-mono text-xs text-ink-muted">Display · Bold</p>
                    <p class="tabular text-5xl font-bold tracking-[-0.035em] text-ink">€1,487.50</p>
                </div>
                <div class="grid gap-2 p-5 sm:grid-cols-[10rem_minmax(0,1fr)] sm:items-baseline sm:p-6">
                    <p class="font-mono text-xs text-ink-muted">Headline · Bold</p>
                    <p class="text-3xl font-bold tracking-[-0.03em] text-ink">Germany VAT calculator</p>
                </div>
                <div class="grid gap-2 p-5 sm:grid-cols-[10rem_minmax(0,1fr)] sm:items-baseline sm:p-6">
                    <p class="font-mono text-xs text-ink-muted">Body · Regular</p>
                    <p class="max-w-[68ch] text-base leading-7 text-ink-muted">The standard VAT rate in Germany is 19%. Reduced rates of 7% apply to selected goods and services.</p>
                </div>
                <div class="grid gap-2 p-5 sm:grid-cols-[10rem_minmax(0,1fr)] sm:items-baseline sm:p-6">
                    <p class="font-mono text-xs text-ink-muted">Kicker · Semibold</p>
                    <p class="text-xs font-semibold tracking-[0.08em] text-ink-muted uppercase">EU member</p>
                </div>
            </div>
        </section>

        <section aria-labelledby="brand-voice" class="space-y-6">
            <header class="max-w-[68ch]">
                <h2 id="brand-voice" class="text-2xl font-bold tracking-[-0.02em] text-ink">Voice</h2>
                <p class="mt-2 text-base leading-7 text-ink-muted">Plain, calm and exact, like a good reference book. In all 24 languages the copy says what is true, how current it is and where it comes from.</p>
            </header>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                @foreach($voice as [$principle, $do, $dont])
                    <div class="app-surface p-5">
                        <h3 class="font-bold text-ink">{{ $principle }}</h3>
                        <dl class="mt-3 space-y-2 text-sm leading-6">
                            <div class="flex gap-2">
                                <dt class="shrink-0"><x-ui.icon name="check" class="mt-1 size-4 text-success" /><span class="sr-only">Write</span></dt>
                                <dd class="text-ink">{{ $do }}</dd>
                            </div>
                            <div class="flex gap-2">
                                <dt class="shrink-0"><x-ui.icon name="x" class="mt-1 size-4 text-danger" /><span class="sr-only">Not</span></dt>
                                <dd class="text-ink-muted line-through decoration-ink-quiet">{{ $dont }}</dd>
                            </div>
                        </dl>
                    </div>
                @endforeach
            </div>
        </section>

        <section aria-labelledby="brand-credit" class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <div class="max-w-[68ch]">
                <h2 id="brand-credit" class="text-2xl font-bold tracking-[-0.02em] text-ink">Credit our data</h2>
                <p class="mt-2 text-base leading-7 text-ink-muted">The VAT rates and the dataset are free to reuse under CC BY 4.0. Name EU VAT Info and link to the page you used, and keep the update date when you show a rate.</p>
            </div>
            <figure class="app-surface overflow-hidden">
                <figcaption class="border-b border-line px-5 py-3 text-sm font-semibold text-ink">Suggested credit</figcaption>
                <p class="p-5 font-mono text-sm leading-6 text-ink">VAT rates: <a href="{{ $canonical }}" class="app-link">EU VAT Info</a> ({{ parse_url($canonical, PHP_URL_HOST) }}), from European Commission data, CC BY 4.0.</p>
            </figure>
        </section>

        <section aria-labelledby="brand-resources" class="space-y-6">
            <h2 id="brand-resources" class="text-2xl font-bold tracking-[-0.02em] text-ink">Build with it</h2>
            <ul class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                @foreach($resources as [$icon, $title, $description, $href])
                    <li>
                        <a href="{{ $href }}" class="app-surface group flex h-full flex-col gap-3 p-5 transition-colors hover:border-line-strong">
                            <span class="flex size-10 items-center justify-center rounded-card bg-action-soft text-action-deep"><x-ui.icon :name="$icon" class="size-5" /></span>
                            <span class="font-bold text-ink group-hover:text-action">{{ $title }}</span>
                            <span class="text-sm leading-6 text-ink-muted">{{ $description }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </section>
    </div>
</div>
