@use('App\Livewire\Styleguide')
@use('App\Support\DesignSystem\DesignTokens')

@php
    $pageUrl = url('/styleguide');
    $tokensUrl = url('/styleguide/design.tokens.json');
    $markdownUrl = url('/styleguide.md');
    $swatches = [
        'workspace' => 'bg-workspace',
        'surface' => 'bg-surface',
        'surface-subtle' => 'bg-surface-subtle',
        'surface-muted' => 'bg-surface-muted',
        'line' => 'bg-line',
        'line-strong' => 'bg-line-strong',
        'ink' => 'bg-ink',
        'ink-muted' => 'bg-ink-muted',
        'ink-quiet' => 'bg-ink-quiet',
        'brand' => 'bg-brand',
        'brand-deep' => 'bg-brand-deep',
        'brand-soft' => 'bg-brand-soft',
        'action' => 'bg-action',
        'action-deep' => 'bg-action-deep',
        'action-soft' => 'bg-action-soft',
        'button' => 'bg-button',
        'button-hover' => 'bg-button-hover',
        'success' => 'bg-success',
        'success-soft' => 'bg-success-soft',
        'warning' => 'bg-warning',
        'warning-soft' => 'bg-warning-soft',
        'danger' => 'bg-danger',
        'danger-soft' => 'bg-danger-soft',
        'code' => 'bg-code',
        'code-raised' => 'bg-code-raised',
        'code-muted' => 'bg-code-muted',
    ];
    $syntaxText = [
        'keyword' => 'text-syntax-keyword',
        'function' => 'text-syntax-function',
        'string' => 'text-syntax-string',
        'literal' => 'text-syntax-literal',
        'comment' => 'text-syntax-comment',
        'ok' => 'text-syntax-ok',
        'error' => 'text-syntax-error',
    ];
    $radiusClass = ['control' => 'rounded-control', 'card' => 'rounded-card', 'panel' => 'rounded-panel'];
    $shadowClass = ['card' => 'shadow-card', 'workflow' => 'shadow-workflow', 'floating' => 'shadow-floating'];
    $rem = fn (string $value) => str_ends_with($value, 'rem') ? rtrim(rtrim(number_format((float) $value * 16, 2, '.', ''), '0'), '.').'px' : $value;
    $rating = fn (float $ratio) => match (true) {
        $ratio >= 7 => ['AAA', 'bg-success-soft text-success'],
        $ratio >= 4.5 => ['AA', 'bg-success-soft text-success'],
        $ratio >= 3 => ['Large text and UI', 'bg-warning-soft text-warning'],
        default => ['Decorative only', 'bg-danger-soft text-danger'],
    };
    $markdown = fn (string $text) => Str::inlineMarkdown($text, ['html_input' => 'escape', 'allow_unsafe_links' => false]);
    $northStar = preg_match('/\*\*North star: (.+?)\*\* (.+)/', $guide->markdown(), $star) ? ['title' => rtrim($star[1], '.'), 'body' => $star[2]] : null;
    $iconTag = fn (string $name) => sprintf('<%s name="%s" class="size-4" />', 'x-ui.icon', $name);
    $inlineCode = '[&_code]:rounded-xs [&_code]:bg-surface-muted [&_code]:px-1 [&_code]:font-mono [&_code]:text-[0.8125rem] [&_code]:text-ink sm:[&_code]:whitespace-nowrap';
    $rules = collect($guide->numberedList('Enforcement'))->map(fn (string $rule) => rtrim(str_replace('this file', 'DESIGN.md', $rule), ';.'));
    $templateRules = $rules->reject(fn (string $rule) => str_starts_with($rule, 'or when '));
    $documentationRules = $rules->filter(fn (string $rule) => str_starts_with($rule, 'or when '))->map(fn (string $rule) => Str::after($rule, 'or when '));
    $breadcrumbCode = sprintf('<%s :items="[__(\'ui.nav.vat_calculator\') => locale_path(\'/vat-calculator\'), $country->name => \'\']" />', 'x-site-breadcrumbs');
    $keyTokens = [
        'button-edge' => 'bg-button-edge',
        'key-face' => 'bg-key-face',
        'key-edge' => 'bg-(--ui-key-edge)',
    ];
    $glyphs = ['bg-(--ui-glyph-1)', 'bg-(--ui-glyph-2)', 'bg-(--ui-glyph-3)', 'bg-(--ui-glyph-4)', 'bg-action-deep'];
@endphp

@section('seo')
    <x-seo-meta
        title="Design system: tokens, components and patterns | EU VAT Info"
        description="The EU VAT Info design system: colour, type, shape and motion tokens, accessible components and page patterns, with DTCG tokens and a Markdown guide."
        :url="$pageUrl"
        type="article">
        <x-json-ld :data="[
            '@type' => 'TechArticle',
            'headline' => 'The EU VAT Info design system',
            'description' => 'Tokens, components and page patterns behind EU VAT Info, rendered with the production stylesheet.',
            'url' => $pageUrl,
            'inLanguage' => 'en',
            'version' => $guide->version(),
            'isPartOf' => ['@type' => 'WebSite', 'name' => __('ui.site_name'), 'url' => url('/')],
            'publisher' => ['@type' => 'Organization', 'name' => __('ui.site_name'), 'url' => url('/')],
            'hasPart' => [
                ['@type' => 'Dataset', 'name' => 'EU VAT Info design tokens', 'encodingFormat' => 'application/design-tokens+json', 'url' => $tokensUrl, 'isAccessibleForFree' => true],
                ['@type' => 'CreativeWork', 'name' => 'DESIGN.md', 'encodingFormat' => 'text/markdown', 'url' => $markdownUrl],
            ],
        ]" />
        <x-json-ld :data="[
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                ['@type' => 'ListItem', 'position' => 1, 'name' => __('ui.site_name'), 'item' => url('/')],
                ['@type' => 'ListItem', 'position' => 2, 'name' => __('ui.styleguide.nav_label'), 'item' => $pageUrl],
            ],
        ]" />
    </x-seo-meta>
@endsection

@push('head')
    <link rel="alternate" type="text/markdown" href="{{ $markdownUrl }}" title="DESIGN.md">
    <link rel="alternate" type="application/design-tokens+json" href="{{ $tokensUrl }}" title="Design tokens">
@endpush

<div>
    <section class="hero-field">
        <x-hero-backdrop />
        <div class="app-container relative pb-10 pt-6 sm:pb-14 sm:pt-8">
            <x-site-breadcrumbs :items="[__('ui.styleguide.nav_label') => '']" />

            <div class="mt-6 max-w-3xl" lang="en">
                <h1 class="max-w-2xl text-4xl font-semibold tracking-[-0.03em] text-ink sm:text-5xl sm:leading-[1.04]">The EU VAT Info design system</h1>
                <p class="mt-4 max-w-[54ch] text-base leading-7 text-ink-muted sm:text-lg">Tokens, components and page patterns behind EU VAT Info. Every example renders with the production stylesheet, so what you see here is what ships.</p>
                <div class="mt-8 flex flex-wrap gap-3">
                    <a href="{{ $tokensUrl }}" download="eu-vat-info.tokens.json" class="app-button-primary">
                        <x-ui.icon name="download" class="size-4" />
                        Download tokens
                    </a>
                    <a href="{{ $markdownUrl }}" class="app-button-secondary">
                        <x-ui.icon name="file-text" class="size-4" />
                        Read DESIGN.md
                    </a>
                    <a href="{{ url('/storybook/') }}" class="app-button-secondary">
                        <x-ui.icon name="puzzle" class="size-4" />
                        Browse Storybook
                    </a>
                    <a href="{{ locale_path('/brand') }}" class="app-button-secondary">
                        <x-ui.icon name="flag" class="size-4" />
                        Brand guidelines
                    </a>
                </div>
            </div>

            <dl class="mt-10 grid grid-cols-2 gap-px overflow-hidden rounded-card border border-line bg-line sm:grid-cols-3 xl:grid-cols-6" lang="en">
                @foreach($stats as $stat)
                    <div class="flex flex-col-reverse bg-surface px-4 py-3.5">
                        <dt class="mt-1 font-display text-[0.8125rem] font-medium text-ink-muted">{{ $stat['label'] }}</dt>
                        <dd class="tabular font-display text-3xl font-semibold tracking-[-0.02em] text-ink">{{ $stat['value'] }}</dd>
                    </div>
                @endforeach
            </dl>

            <div class="mt-6 flex flex-wrap items-center gap-x-6 gap-y-2" lang="en">
                <p class="tabular font-display text-sm font-medium text-ink-muted">DESIGN.md version {{ $guide->version() }}</p>
                <a href="{{ Styleguide::BUSINESSPRESS_URL }}" target="_blank" rel="noopener" class="group inline-flex items-center gap-2 rounded-control font-display text-sm font-medium text-ink-muted transition-colors hover:text-ink">
                    <span class="underline decoration-action/30 underline-offset-4 group-hover:decoration-current">Get a design system like this for your business on BusinessPress</span>
                    <x-ui.icon name="arrow-up-right" class="size-4 text-action" />
                <x-ui.new-tab /></a>
            </div>
        </div>
    </section>

    <div class="app-container py-8 sm:py-10">
        @if(app()->getLocale() !== 'en')
            <p class="mb-8 inline-flex items-center gap-2 text-sm text-ink-muted">
                <x-ui.icon name="languages" class="size-4" />
                {{ __('ui.styleguide.english_only') }}
            </p>
        @endif

        <div class="grid grid-cols-1 gap-10 lg:grid-cols-[13.5rem_minmax(0,1fr)] xl:gap-14" lang="en">
            <aside class="lg:sticky lg:top-24 lg:max-h-[calc(100dvh-7rem)] lg:self-start lg:overflow-y-auto lg:overscroll-contain">
                <details class="app-surface group lg:hidden">
                    <summary class="flex min-h-12 cursor-pointer list-none items-center justify-between gap-3 px-4 text-sm font-semibold text-ink [&::-webkit-details-marker]:hidden">
                        <span class="inline-flex items-center gap-2">
                            <x-ui.icon name="menu" class="size-4 text-ink-quiet" />
                            Jump to a section
                        </span>
                        <x-ui.icon name="chevron-down" class="size-4 text-ink-quiet transition-transform duration-150 group-open:rotate-180" />
                    </summary>
                    <div class="grid grid-cols-2 gap-x-4 gap-y-4 border-t border-line p-4">
                        @foreach(Styleguide::SECTIONS as $group => $sections)
                            <div>
                                <p class="app-eyebrow">{{ $group }}</p>
                                <ul class="mt-1.5 space-y-0.5 text-sm">
                                    @foreach($sections as $id => $label)
                                        <li><a href="#{{ $id }}" class="flex min-h-9 items-center text-ink-muted hover:text-action">{{ $label }}</a></li>
                                    @endforeach
                                </ul>
                            </div>
                        @endforeach
                    </div>
                </details>

                <nav class="hidden lg:block" aria-label="Design system sections" x-data="sectionNav">
                    @foreach(Styleguide::SECTIONS as $group => $sections)
                        <p @class(['app-eyebrow px-3', 'mt-4' => ! $loop->first])>{{ $group }}</p>
                        <ul class="mt-1 text-sm">
                            @foreach($sections as $id => $label)
                                <li>
                                    <a
                                        href="#{{ $id }}"
                                        class="flex min-h-7 items-center rounded-control border-l-2 border-transparent px-3 text-ink-muted transition-colors hover:bg-surface-muted hover:text-ink aria-[current=true]:border-action aria-[current=true]:bg-action-soft aria-[current=true]:font-semibold aria-[current=true]:text-action-deep"
                                        :aria-current="current === @js($id) ? 'true' : null"
                                    >{{ $label }}</a>
                                </li>
                            @endforeach
                        </ul>
                    @endforeach
                </nav>
            </aside>

            <div class="min-w-0 space-y-20">
                {{-- Foundations --}}
                <x-styleguide.section id="principles" title="Principles" lead="Seven decisions every page follows. They explain the rules further down and settle anything the rules do not cover.">
                    @if($northStar)
                        <figure class="cut rounded-panel border-l-2 border-action bg-action-soft px-5 py-4 sm:px-6 sm:py-5">
                            <figcaption class="font-display text-xl font-semibold tracking-[-0.02em] text-ink">North star: {{ $northStar['title'] }}</figcaption>
                            <blockquote class="mt-2 max-w-[72ch] text-base leading-7 text-ink-muted">{!! $markdown($northStar['body']) !!}</blockquote>
                        </figure>
                    @endif
                    <ol class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        @foreach($guide->numberedList('Principles') as $principle)
                            @php([$heading, $body] = preg_match('/^\*\*(.+?)\*\*\s*(.*)$/', $principle, $parts) ? [rtrim($parts[1], '.'), $parts[2]] : ['', $principle])
                            <li class="app-surface flex gap-4 p-5">
                                <span class="tabular flex size-8 shrink-0 items-center justify-center rounded-full bg-action-soft text-sm font-semibold text-action-deep" aria-hidden="true">{{ $loop->iteration }}</span>
                                <div>
                                    <h3 class="font-semibold text-ink">{{ $heading }}</h3>
                                    <p class="mt-1 text-sm leading-6 text-ink-muted">{!! $markdown($body) !!}</p>
                                </div>
                            </li>
                        @endforeach
                    </ol>
                </x-styleguide.section>

                <x-styleguide.section id="colour" title="Colour" lead="Semantic tokens carry meaning, not hue. Each one has a light and a dark value, and the data-theme attribute on the html element swaps them, so templates never need dark: variants.">
                    @foreach(DesignTokens::SEMANTIC_GROUPS as $group => $names)
                        <div>
                            <h3 class="text-lg font-semibold text-ink">{{ $group }}</h3>
                            <ul class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-3">
                                @foreach($names as $name)
                                    @continue(! isset($colors[$name]))
                                    @php($token = $colors[$name])
                                    <li class="app-surface overflow-hidden">
                                        <div class="grid h-16 grid-cols-2 border-b border-line" aria-hidden="true">
                                            <span data-theme="light" class="{{ $swatches[$name] }}"></span>
                                            <span data-theme="dark" class="{{ $swatches[$name] }}"></span>
                                        </div>
                                        <div class="p-3.5">
                                            <p class="flex items-center justify-between gap-2">
                                                <code class="font-mono text-sm font-semibold text-ink">{{ $name }}</code>
                                                <button type="button" class="app-button-ghost h-7 min-h-7 px-2 text-xs" x-data x-on:click="$copy(@js($token['utility']), @js($token['utility'].' copied'))" aria-label="Copy {{ $token['utility'] }}">
                                                    <x-ui.icon name="copy" class="size-3.5" />
                                                    {{ $token['utility'] }}
                                                </button>
                                            </p>
                                            <dl class="mt-2 grid grid-cols-2 gap-2 text-xs">
                                                <div>
                                                    <dt class="text-ink-quiet">Light</dt>
                                                    <dd class="font-mono text-ink">{{ strtoupper($token['light']->hex()) }}</dd>
                                                </div>
                                                <div>
                                                    <dt class="text-ink-quiet">Dark</dt>
                                                    <dd class="font-mono text-ink">{{ strtoupper($token['dark']->hex()) }}</dd>
                                                </div>
                                            </dl>
                                            @if($uses[$name]['Use'] ?? null)
                                                <p class="mt-2 text-xs leading-5 text-ink-muted">{!! $markdown($uses[$name]['Use']) !!}</p>
                                            @endif
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endforeach

                    <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
                        <div>
                            <h3 class="text-lg font-semibold text-ink">Fixed colours</h3>
                            <p class="mt-1 text-sm leading-6 text-ink-muted">The same in both themes: the code surfaces, which stay graphite in light and dark.</p>
                            <ul class="mt-3 grid grid-cols-2 gap-3">
                                @foreach($tokens->fixedColors() as $name => $token)
                                    <li class="app-surface overflow-hidden">
                                        <span class="block h-12 border-b border-line {{ $swatches[$name] ?? '' }}" aria-hidden="true"></span>
                                        <p class="flex flex-wrap items-center justify-between gap-x-2 gap-y-0.5 p-3">
                                            <code class="font-mono text-sm font-semibold text-ink">{{ $name }}</code>
                                            <span class="font-mono text-xs text-ink-muted">{{ strtoupper($token['color']->hex()) }}</span>
                                        </p>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-ink">Syntax colours</h3>
                            <p class="mt-1 text-sm leading-6 text-ink-muted">For code samples on the graphite code surface, in both themes. Syntax stays in the cobalt family; only status codes take green and red.</p>
                            <ul class="app-code mt-3 grid grid-cols-2 gap-x-4 gap-y-2.5 p-4 font-mono text-sm">
                                @foreach($tokens->syntaxColors() as $name => $token)
                                    <li class="flex items-center justify-between gap-3">
                                        <span class="{{ $syntaxText[$name] ?? '' }}">{{ $name }}</span>
                                        <span class="text-xs text-white/60">{{ strtoupper($token['color']->hex()) }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
                        <div>
                            <h3 class="text-lg font-semibold text-ink">Keys</h3>
                            <p class="mt-1 text-sm leading-6 text-ink-muted">The faces and bottom edges of keys and chips. They reach templates through the app-button and app-chip classes and the shadow-key utilities.</p>
                            <ul class="mt-3 space-y-3">
                                @foreach($keyTokens as $name => $class)
                                    <li class="app-surface flex items-center gap-4 overflow-hidden pr-3.5">
                                        <span class="grid h-16 w-24 shrink-0 grid-cols-2 border-r border-line" aria-hidden="true">
                                            <span data-theme="light" class="{{ $class }}"></span>
                                            <span data-theme="dark" class="{{ $class }}"></span>
                                        </span>
                                        <span class="min-w-0 py-2.5">
                                            <code class="block font-mono text-sm font-semibold text-ink">{{ $name }}</code>
                                            <span class="block text-xs leading-5 text-ink-muted">{!! $markdown($uses[$name]['Use'] ?? '') !!}</span>
                                        </span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-ink">Rate field tints</h3>
                            <p class="mt-1 text-sm leading-6 text-ink-muted">glyph-1 to glyph-4, lightest to densest, with action-deep for the densest cells. The rateField component reads them, and a graphite band swaps in its own set.</p>
                            <div class="app-surface mt-3 divide-y divide-line overflow-hidden">
                                @foreach(['light' => 'Light', 'dark' => 'Dark', 'graphite' => 'Graphite'] as $scope => $label)
                                    <div @if($scope !== 'graphite') data-theme="{{ $scope }}" @endif @class(['flex items-center gap-4 px-4 py-3', 'bg-workspace text-ink-muted' => $scope !== 'graphite', 'hero-canvas text-white/75' => $scope === 'graphite'])>
                                        <span class="w-16 shrink-0 font-display text-xs font-medium">{{ $label }}</span>
                                        <span class="grid flex-1 grid-cols-5 gap-1" aria-hidden="true">
                                            @foreach($glyphs as $glyph)
                                                <span class="h-8 w-full rounded-xs {{ $glyph }}"></span>
                                            @endforeach
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <div>
                        <h3 class="text-lg font-semibold text-ink">VAT map ramp</h3>
                        <p class="mt-1 max-w-[68ch] text-sm leading-6 text-ink-muted">A single-hue ordinal ramp, checked for colour-vision deficiency. It flips in dark mode so higher rates gain contrast against the dark surface, and every region is also listed as text.</p>
                        <div class="app-surface mt-3 divide-y divide-line overflow-hidden">
                            @foreach(['light' => 'Light', 'dark' => 'Dark'] as $scope => $label)
                                <div data-theme="{{ $scope }}" class="flex items-center gap-4 bg-surface px-4 py-3">
                                    <span class="w-10 shrink-0 text-xs font-semibold text-ink-muted">{{ $label }}</span>
                                    <span class="grid flex-1 grid-cols-5 gap-1" aria-hidden="true">
                                        @foreach($tokens->mapRamp() as $step => $token)
                                            <span class="eu-map-swatch eu-map-swatch-{{ $step }} h-8 w-full rounded-xs"></span>
                                        @endforeach
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div>
                        <h3 class="text-lg font-semibold text-ink">Contrast pairs</h3>
                        <p class="mt-1 max-w-[68ch] text-sm leading-6 text-ink-muted">WCAG 2.2 contrast ratios computed from the tokens above. Body text needs 4.5:1; large text, icons and borders need 3:1.</p>
                        <div class="app-surface mt-3 overflow-hidden">
                            <table class="app-table">
                                <thead>
                                    <tr>
                                        <th scope="col" class="pl-4 sm:pl-5">Pair</th>
                                        <th scope="col" class="hidden md:table-cell">Tokens</th>
                                        <th scope="col" class="text-right">Light</th>
                                        <th scope="col" class="pr-4 text-right sm:pr-5">Dark</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($contrast as $pair)
                                        <tr>
                                            <td class="pl-4 sm:pl-5">
                                                <span class="block font-semibold text-ink">{{ $pair['label'] }}</span>
                                                <span class="block font-mono text-xs text-ink-muted md:hidden">{{ $pair['foreground'] }} on {{ $pair['background'] }}</span>
                                            </td>
                                            <td class="hidden font-mono text-xs text-ink-muted md:table-cell">{{ $pair['foreground'] }} on {{ $pair['background'] }}</td>
                                            @foreach(['light' => '', 'dark' => 'pr-4 sm:pr-5'] as $theme => $padding)
                                                @php([$grade, $tone] = $rating($pair[$theme]))
                                                <td class="{{ $padding }} text-right">
                                                    <span class="inline-flex flex-col items-end gap-1 sm:flex-row sm:items-center sm:gap-2">
                                                        <span class="tabular font-semibold text-ink">{{ number_format($pair[$theme], 1) }}:1</span>
                                                        <span class="app-badge {{ $tone }}">{{ $grade }}</span>
                                                    </span>
                                                </td>
                                            @endforeach
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </x-styleguide.section>

                <x-styleguide.section id="typography" title="Typography" lead="Familjen Grotesk at weight 600 for headings and figures, IBM Plex Sans for reading and JetBrains Mono for machine values, all three self-hosted. Headings stay one colour, labels stay in sentence case, and every figure that can change or be compared is set in tabular numbers.">
                    <div class="app-surface divide-y divide-line">
                        @foreach([
                            ['Display', 'text-4xl sm:text-5xl font-semibold tracking-[-0.03em] sm:leading-[1.04]', 'display', 'Hero headings, and hero results with font-display on the figure'],
                            ['Headline', 'text-3xl sm:text-[2.5rem] font-semibold tracking-[-0.03em]', 'headline', 'Page header titles'],
                            ['Section', 'text-xl font-semibold text-ink', null, 'Section headings inside a page'],
                            ['Title', 'text-lg font-semibold text-ink', 'title', 'Card titles'],
                            ['Body', 'text-base leading-7 text-ink-muted', 'body', 'Paragraphs, capped at 68–72 characters'],
                            ['Small', 'text-sm text-ink-muted', null, 'Helper text and table cells'],
                            ['Label', 'font-display text-[0.8125rem] font-medium text-ink-muted', 'label', 'Field labels, data labels and table heads, in sentence case and untracked'],
                            ['Mono', 'font-mono', null, 'VAT numbers, API paths, code, the meter and the rate field; never a label or heading'],
                        ] as [$role, $classes, $scale, $use])
                            <div class="grid grid-cols-1 gap-3 p-5 md:grid-cols-[12rem_minmax(0,1fr)] md:gap-6">
                                <div>
                                    <p class="font-semibold text-ink">{{ $role }}</p>
                                    <p class="mt-0.5 text-xs leading-5 text-ink-muted">{{ $use }}</p>
                                    @if($scale && ($style = $guide->typeScale()[$scale] ?? null))
                                        <p class="tabular mt-1.5 flex flex-wrap gap-x-3 font-mono text-[0.6875rem] text-ink-quiet">
                                            @foreach(array_filter([$style['size'] ?? null, $style['weight'] ?? null, $style['lineHeight'] ?? null]) as $fact)
                                                <span>{{ $fact }}</span>
                                            @endforeach
                                        </p>
                                    @endif
                                </div>
                                <div class="min-w-0">
                                    @switch($role)
                                        @case('Display')
                                            <p class="tabular font-display text-4xl font-semibold tracking-[-0.03em] text-ink sm:text-5xl sm:leading-[1.04]">€1,487.50</p>
                                            @break
                                        @case('Headline')
                                            <p class="font-display text-3xl font-semibold tracking-[-0.03em] text-ink sm:text-[2.5rem] sm:leading-[1.1]">VAT rate change history</p>
                                            @break
                                        @case('Section')
                                            <p class="font-display text-xl font-semibold tracking-[-0.02em] text-ink">Recorded changes</p>
                                            @break
                                        @case('Title')
                                            <p class="font-display text-lg font-semibold tracking-[-0.02em] text-ink">Germany VAT rates and formulas</p>
                                            @break
                                        @case('Body')
                                            <p class="max-w-[68ch] text-base leading-7 text-ink-muted">VIES forwards your query to the national VAT database of the member state that issued the number and returns its registration status.</p>
                                            @break
                                        @case('Small')
                                            <p class="text-sm text-ink-muted">The country is detected from the VAT number prefix.</p>
                                            @break
                                        @case('Mono')
                                            <p class="font-mono text-sm text-ink">DE123456789</p>
                                            @break
                                        @default
                                            <p class="font-display text-[0.8125rem] font-medium text-ink-muted">Amount (excl. VAT)</p>
                                    @endswitch
                                    <code class="mt-2 block break-words font-mono text-xs text-action-deep">{{ $classes }}</code>
                                </div>
                            </div>
                        @endforeach
                        <div class="grid grid-cols-1 gap-3 p-5 md:grid-cols-[12rem_minmax(0,1fr)] md:gap-6">
                            <div>
                                <p class="font-semibold text-ink">Context label and kicker</p>
                                <p class="mt-0.5 text-xs leading-5 text-ink-muted">A short label that never stands above a heading: a group name or a source line, and the flag line in a country heading. Sentence case, never tracked capitals</p>
                            </div>
                            <div class="flex flex-wrap items-center gap-4">
                                <p class="app-eyebrow">Source: European Commission</p>
                                <p class="app-kicker">EU member state</p>
                                <code class="block w-full font-mono text-xs text-action-deep">app-eyebrow, app-kicker</code>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 gap-3 p-5 md:grid-cols-[12rem_minmax(0,1fr)] md:gap-6">
                            <div>
                                <p class="font-semibold text-ink">Tabular figures</p>
                                <p class="mt-0.5 text-xs leading-5 text-ink-muted">Digits share one width, so columns of money and rates line up</p>
                            </div>
                            <div class="grid max-w-md grid-cols-2 gap-4 font-display text-lg font-semibold text-ink">
                                <div>
                                    <p class="text-xs font-medium text-ink-quiet">Default</p>
                                    <p>€1,111.11</p>
                                    <p>€9,090.00</p>
                                </div>
                                <div>
                                    <p class="text-xs font-medium text-ink-quiet">With <code class="font-mono">tabular</code></p>
                                    <p class="tabular">€1,111.11</p>
                                    <p class="tabular">€9,090.00</p>
                                </div>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 gap-3 p-5 md:grid-cols-[12rem_minmax(0,1fr)] md:gap-6">
                            <div>
                                <p class="font-semibold text-ink">Families</p>
                                <p class="mt-0.5 text-xs leading-5 text-ink-muted">From the --font-display, --font-sans and --font-mono tokens. Three weights are in use: 400, 500 and 600, which font-bold also resolves to</p>
                            </div>
                            <div class="space-y-5">
                                <div>
                                    <p class="font-display text-3xl font-semibold tracking-[-0.03em] text-ink">Familjen Grotesk 19%</p>
                                    <p class="mt-1 text-xs text-ink-muted">{{ implode(', ', $tokens->fonts()['display'] ?? []) }}</p>
                                </div>
                                <div>
                                    <p class="flex flex-wrap items-baseline gap-x-5 gap-y-1 text-2xl text-ink">
                                        <span class="font-normal">Plex 400</span>
                                        <span class="font-medium">Plex 500</span>
                                        <span class="font-semibold">Plex 600</span>
                                    </p>
                                    <p class="mt-1 text-xs text-ink-muted">{{ implode(', ', $tokens->fonts()['sans'] ?? []) }}</p>
                                </div>
                                <div>
                                    <p class="flex flex-wrap gap-x-5 gap-y-1 font-mono text-sm text-ink">
                                        <span>LT100019070512</span>
                                        <span>country_code</span>
                                        <span>░░░░████</span>
                                    </p>
                                    <p class="mt-1 text-xs text-ink-muted">{{ implode(', ', $tokens->fonts()['mono'] ?? []) }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </x-styleguide.section>

                <x-styleguide.section id="shape" title="Shape" lead="Three radius tokens and two cuts. Keys, fields and chips take 6px and cards 8px; the primary task card is cut on two opposite corners, and fields and chips take the smaller cut. Pills are not part of the system: rounded-full is kept for spinners, avatars and numbered steps.">
                    <ul class="grid grid-cols-2 gap-3 md:grid-cols-3">
                        @foreach($tokens->radii() as $name => $value)
                            <li class="app-surface p-4">
                                <span class="block h-16 border-2 border-action bg-action-soft {{ $radiusClass[$name] ?? '' }}" aria-hidden="true"></span>
                                <p class="mt-3 flex flex-wrap items-baseline justify-between gap-x-2">
                                    <code class="font-mono text-sm font-semibold text-ink">{{ $radiusClass[$name] ?? $name }}</code>
                                    <span class="tabular font-mono text-xs text-ink-muted">{{ $rem($value) }}</span>
                                </p>
                                <p class="mt-1 text-xs leading-5 text-ink-muted">{!! $markdown($guide->tokenRows('Shape')['radius-'.$name]['Use'] ?? '') !!}</p>
                            </li>
                        @endforeach
                        @foreach([['cut', '16px bevel', 'Two opposite corners bevelled on a panel where corner-shape is supported'], ['cut-sm', '8px bevel', 'The same on fields and chips'], ['rounded-xs', '2px', 'Flags, badges, keyboard hints and bar ends'], ['rounded-full', '50%', 'Spinners, avatars and numbered step markers only']] as [$class, $size, $use])
                            <li class="app-surface p-4">
                                <span @class(['block h-16 border-2', 'border-action bg-action-soft' => str_starts_with($class, 'cut'), 'border-line-strong bg-surface-muted' => ! str_starts_with($class, 'cut'), 'cut rounded-panel' => $class === 'cut', 'cut-sm rounded-control' => $class === 'cut-sm', 'rounded-xs' => $class === 'rounded-xs', 'mx-auto w-16 rounded-full' => $class === 'rounded-full']) aria-hidden="true"></span>
                                <p class="mt-3 flex flex-wrap items-baseline justify-between gap-x-2">
                                    <code class="font-mono text-sm font-semibold text-ink">{{ $class }}</code>
                                    <span class="tabular font-mono text-xs text-ink-muted">{{ $size }}</span>
                                </p>
                                <p class="mt-1 text-xs leading-5 text-ink-muted">{{ $use }}</p>
                            </li>
                        @endforeach
                    </ul>
                </x-styleguide.section>

                <x-styleguide.section id="elevation" title="Elevation" lead="Depth is a hairline first, then a paper step. Three shadows lift resting cards, the one primary task card and things that float; keys stand on a real 2px bottom edge and sink onto an inset shadow when pressed.">
                    <ul class="grid grid-cols-1 gap-5 rounded-panel bg-workspace p-5 sm:p-8 md:grid-cols-3">
                        @foreach($tokens->shadows() as $name => $value)
                            <li class="rounded-card border border-line bg-surface p-5 {{ $shadowClass[$name] ?? '' }}">
                                <code class="font-mono text-sm font-semibold text-ink">{{ $shadowClass[$name] ?? $name }}</code>
                                <p class="mt-1 text-xs leading-5 text-ink-muted">{!! $markdown($guide->tokenRows('Elevation')['shadow-'.$name]['Use'] ?? '') !!}</p>
                                <p class="tabular mt-3 text-[0.6875rem] text-ink-quiet">{{ count(DesignTokens::shadowLayers($value)) }} {{ Str::plural('layer', count(DesignTokens::shadowLayers($value))) }}</p>
                            </li>
                        @endforeach
                        @foreach([
                            'shadow-key' => 'cut-sm rounded-control border border-line-strong bg-key-face shadow-key',
                            'shadow-key-accent' => 'cut-sm rounded-control border border-button-edge bg-button shadow-key-accent',
                            'shadow-key-press' => 'cut-sm translate-y-0.5 rounded-control border border-action bg-action-soft shadow-key-press',
                        ] as $name => $key)
                            <li class="rounded-card border border-line bg-surface p-5">
                                <span class="block h-11 w-28 {{ $key }}" aria-hidden="true"></span>
                                <code class="mt-4 block font-mono text-sm font-semibold text-ink">{{ $name }}</code>
                                <p class="mt-1 text-xs leading-5 text-ink-muted">{!! $markdown($guide->tokenRows('Elevation')[$name]['Use'] ?? '') !!}</p>
                            </li>
                        @endforeach
                    </ul>
                </x-styleguide.section>

                <x-styleguide.section id="motion" title="Motion" lead="Quiet and quick, all on the out-quint curve. Keys travel 1–2px and change colour in the short duration, thumbs and menus move in the longer one, and the rate field drifts at 30 fps and pauses off screen. Nothing scales or springs, and reduced-motion settings collapse every duration and still the field.">
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                        @foreach($tokens->easings() as $name => $curve)
                            <div class="app-surface p-5">
                                <p class="text-xs font-semibold text-ink-muted">Easing</p>
                                <code class="mt-1 block font-mono text-sm font-semibold text-ink">ease-{{ $name }}</code>
                                <p class="tabular mt-1 font-mono text-xs text-ink-muted">cubic-bezier({{ implode(', ', $curve) }})</p>
                            </div>
                        @endforeach
                        @foreach($tokens->durations() as $name => $milliseconds)
                            <div class="app-surface p-5">
                                <p class="text-xs font-semibold text-ink-muted">{{ ucfirst($name) }} changes</p>
                                <p class="tabular mt-1 text-2xl font-semibold text-ink">{{ $milliseconds }}ms</p>
                                <p class="mt-1 text-xs text-ink-muted">{{ $name === 'colour' ? 'Key travel and pressable feedback on colour, borders and shadows' : 'Sliding thumbs and menus that drop 4px' }}</p>
                            </div>
                        @endforeach
                    </div>
                    <div class="app-surface p-5" x-data="{ moved: false }">
                        <div class="flex flex-wrap items-center justify-between gap-4">
                            <p class="text-sm text-ink-muted">Position change at {{ $tokens->durations()['position'] ?? 200 }}ms on ease-out-quint</p>
                            <button type="button" class="app-button-secondary h-9 min-h-9 px-3 text-xs" x-on:click="moved = !moved">
                                <x-ui.icon name="play" class="size-3.5" />
                                Replay
                            </button>
                        </div>
                        <div class="relative mt-4 h-10 rounded-control border border-line bg-surface-muted p-1 shadow-key-press">
                            <span class="block h-full w-1/4 rounded-xs border border-line-strong bg-key-face shadow-key transition-transform duration-200 ease-out-quint" :class="moved && 'translate-x-[300%]'" aria-hidden="true"></span>
                        </div>
                    </div>
                </x-styleguide.section>

                <x-styleguide.section id="layout" title="Layout" lead="One page width, a four-pixel spacing grid and grids that start from a single column, so no page scrolls sideways at 320px.">
                    @php($container = $tokens->container())
                    <div class="app-surface p-5 sm:p-6">
                        <p class="text-sm font-semibold text-ink">app-container</p>
                        <div class="mt-3 flex h-14 items-stretch overflow-hidden rounded-control border border-dashed border-line-strong text-[0.6875rem] font-semibold text-ink-muted" aria-hidden="true">
                            <span class="flex w-8 items-center justify-center bg-action/15 sm:w-12">↔</span>
                            <span class="flex flex-1 items-center justify-center bg-action-soft text-action-deep">max {{ $container['max'] }} ({{ $rem($container['max']) }})</span>
                            <span class="flex w-8 items-center justify-center bg-action/15 sm:w-12">↔</span>
                        </div>
                        <dl class="mt-4 grid grid-cols-3 gap-3 text-sm">
                            @foreach(['Phones' => 0, 'From sm' => 1, 'From lg' => 2] as $label => $index)
                                <div>
                                    <dt class="text-xs text-ink-muted">{{ $label }}</dt>
                                    <dd class="tabular font-semibold text-ink">{{ $rem($container['gutters'][$index] ?? '0') }} gutters</dd>
                                </div>
                            @endforeach
                        </dl>
                    </div>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div class="app-surface overflow-hidden">
                            <table class="app-table">
                                <caption class="px-5 pb-2 pt-4 text-left text-sm font-semibold text-ink">Breakpoints</caption>
                                <thead>
                                    <tr><th scope="col" class="pl-5">Prefix</th><th scope="col" class="pr-5 text-right">Min width</th></tr>
                                </thead>
                                <tbody>
                                    @foreach(['sm' => 640, 'md' => 768, 'lg' => 1024, 'xl' => 1280, '2xl' => 1536] as $prefix => $width)
                                        <tr><td class="pl-5 font-mono text-ink">{{ $prefix }}:</td><td class="tabular pr-5 text-right text-ink-muted">{{ $width }}px</td></tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="app-surface overflow-hidden">
                            <table class="app-table">
                                <caption class="px-5 pb-2 pt-4 text-left text-sm font-semibold text-ink">Rhythm</caption>
                                <thead>
                                    <tr><th scope="col" class="pl-5">Where</th><th scope="col" class="pr-5 text-right">Classes</th></tr>
                                </thead>
                                <tbody>
                                    @foreach(['Between sections' => 'py-8 sm:py-10', 'Hero bottom' => 'pb-10 sm:pb-14', 'Card padding' => 'p-5 sm:p-6', 'Card grids' => 'gap-4 or gap-6', 'Reading width' => 'max-w-[68ch]'] as $where => $classes)
                                        <tr><td class="pl-5 text-ink">{{ $where }}</td><td class="pr-5 text-right font-mono text-xs text-action-deep">{{ $classes }}</td></tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </x-styleguide.section>

                {{-- Components --}}
                <x-styleguide.section id="buttons" title="Buttons" lead="44px keys that lift 1px on hover and sink 2px on press. One cobalt primary key per view, paper secondary keys or ghost buttons for the rest, and the white and outlined pair only inside a graphite band.">
                    <x-styleguide.specimen example="buttons-variants" />
                    <x-styleguide.specimen example="buttons-keys" />
                    <x-styleguide.specimen example="buttons-on-brand" />
                    <x-styleguide.specimen example="buttons-sizes" />
                    <x-styleguide.specimen example="buttons-states" />
                </x-styleguide.section>

                <x-styleguide.section id="fields" title="Form fields" lead="Fields are cut on two corners and edged in ink-quiet, so the edge holds 3:1 against paper. Labels sit above, help and errors below, linked with aria-describedby. Validate on the client for instant feedback and again on the server.">
                    <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
                        <x-styleguide.specimen example="field-text" />
                        <x-styleguide.specimen example="field-error" />
                        <x-styleguide.specimen example="field-select" />
                        <x-styleguide.specimen example="field-amount" />
                        <x-styleguide.specimen example="field-format-hint" class="xl:col-span-2" />
                    </div>
                </x-styleguide.section>

                <x-styleguide.section id="selection" title="Selection controls" lead="A segmented control for two equal modes on a sunken tray, chips for choosing among values, and chip tabs for switching panels. Chips are small keys that press in when chosen.">
                    <x-styleguide.specimen example="segmented" />
                    <x-styleguide.specimen example="chips" />
                    <x-styleguide.specimen example="tabs" />
                </x-styleguide.section>

                <x-styleguide.section id="surfaces" title="Surfaces" lead="Opaque paper with hairline borders. Cards hold content, the cut raised card holds the primary task, popovers float, the tool hero is paper over the rate field, and brand panels sit only inside a graphite band.">
                    <x-styleguide.specimen example="surface-hero-field" />
                    <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
                        <x-styleguide.specimen example="surface-card" />
                        <x-styleguide.specimen example="surface-raised" />
                        <x-styleguide.specimen example="surface-popover" />
                        <x-styleguide.specimen example="surface-brand-panel" />
                    </div>
                    <x-styleguide.specimen example="surface-cuts" />
                    <x-styleguide.specimen example="surface-note" />
                </x-styleguide.section>

                <x-styleguide.section id="content" title="Text and code" lead="Links, keyboard hints, badges, long-form prose, code and disclosures. Status badges always carry an icon or a word, never colour alone.">
                    <x-styleguide.specimen example="content-inline" />
                    <x-styleguide.specimen example="content-badges" />
                    <x-styleguide.specimen example="content-prose" />
                    <x-styleguide.specimen example="content-code" />
                    <x-styleguide.specimen example="content-disclosure" />
                    <x-styleguide.specimen example="content-faq" />
                </x-styleguide.section>

                <x-styleguide.section id="data" title="Data display" lead="Numbers are the hero. Tables right-align figures in tabular type, breakdowns use definition lists, the calculator total sits over the fifty-cell meter, and bars run on an action track with square ends.">
                    <x-styleguide.specimen example="data-table" />
                    <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
                        <x-styleguide.specimen example="data-breakdown" />
                        <x-styleguide.specimen example="data-meter" />
                    </div>
                    <x-styleguide.specimen example="data-bars" />
                </x-styleguide.section>

                <x-styleguide.section id="navigation" title="Navigation" lead="A paper header with a hairline marks the current section with a 2px cobalt underline, the mobile tab bar marks it with a cobalt top rule, and breadcrumbs lead every page.">
                    <x-styleguide.specimen example="nav-header" />
                    <x-styleguide.specimen
                        title="Breadcrumbs"
                        description="The trail at the top of this page is the live component"
                        :code="$breadcrumbCode"
                    />
                </x-styleguide.section>

                <x-styleguide.section id="overlays" title="Menus and toasts" lead="Popovers fade in with a 4px drop and close on Escape or an outside click. Toasts confirm quick actions without moving the page.">
                    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
                        <x-styleguide.specimen example="overlay-menu" />
                        <x-styleguide.specimen example="overlay-toast" />
                        <x-styleguide.specimen example="overlay-palette" />
                    </div>
                </x-styleguide.section>

                <x-styleguide.section id="icons" title="Icons" lead="Lucide paths at a 1.75 stroke, drawn by one component. Select an icon to copy its tag; add a missing icon to components/ui/icon.blade.php in alphabetical order.">
                    <div class="app-surface overflow-hidden" x-data="{ query: '', names: @js($icons), get term() { return this.query.trim().toLowerCase() }, matches(name) { return name.includes(this.term) } }">
                        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line p-4">
                            <div class="relative w-full max-w-xs">
                                <label for="sg-icon-search" class="sr-only">Filter icons</label>
                                <x-ui.icon name="search" class="pointer-events-none absolute left-3.5 top-1/2 size-4 -translate-y-1/2 text-ink-quiet" />
                                <input id="sg-icon-search" x-model="query" type="search" autocomplete="off" placeholder="Filter {{ count($icons) }} icons…" class="app-field min-h-10 pl-10 text-sm">
                            </div>
                            <code class="font-mono text-xs text-ink-muted">&lt;x-ui.icon name="…" class="size-4" /&gt;</code>
                        </div>
                        <ul class="-mb-px -mr-px grid grid-cols-3 sm:grid-cols-4 xl:grid-cols-6">
                            @foreach($icons as $icon)
                                <li class="border-b border-r border-line" x-show="matches(@js($icon))">
                                    <button type="button" class="pressable flex h-20 w-full flex-col items-center justify-center gap-2.5 px-1 text-ink hover:bg-action-soft hover:text-action-deep sm:h-24 sm:px-2" x-on:click="$copy(@js($iconTag($icon)), @js($icon.' icon copied'))">
                                        <x-ui.icon :name="$icon" class="size-5" />
                                        <span class="max-w-full truncate font-mono text-[0.6875rem] text-ink-muted">{{ $icon }}</span>
                                    </button>
                                </li>
                            @endforeach
                        </ul>
                        <p class="px-4 py-10 text-center text-sm text-ink-muted" x-cloak x-show="!names.some((name) => matches(name))" role="status">
                            No icon matches “<span class="font-semibold text-ink" x-text="term"></span>”. Add it to components/ui/icon.blade.php.
                        </p>
                    </div>
                </x-styleguide.section>

                <x-styleguide.section id="identity" title="Flags and logo" lead="The mark is an ink tile cut on two corners, with a paper slash and dot and a cobalt dot; it inverts with the theme. Flags are local SVGs with a hairline edge, in five sizes; Greece also accepts EL.">
                    <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
                        <x-styleguide.specimen example="identity-logo" />
                        <x-styleguide.specimen example="identity-flags" description="{{ $flagCount }} countries, sizes xs to xl" />
                    </div>
                </x-styleguide.section>

                {{-- Patterns --}}
                <x-styleguide.section id="skeletons" title="Page skeletons" lead="Two page shapes. A tool page opens with a paper hero over the rate field, holding one raised task card; a reference page opens with the paper page header band.">
                    <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
                        <x-styleguide.specimen title="Tool page" description="Calculator, validator, shared result" preview="styleguide.wireframes.tool-page" canvas="subtle" :code="$guide->codeBlocks('Page skeletons')[0] ?? ''" />
                        <x-styleguide.specimen title="Reference page" description="Tables, lists and guides" preview="styleguide.wireframes.reference-page" canvas="subtle" :code="$guide->codeBlocks('Page skeletons')[1] ?? ''" />
                    </div>
                </x-styleguide.section>

                <x-styleguide.section id="results" title="Results" lead="Lead with the answer in display type, restate it in one sentence, break it down, then offer actions. Announce changes through a polite live region.">
                    <x-styleguide.specimen example="pattern-result" />
                    <x-styleguide.specimen example="pattern-freshness" />
                </x-styleguide.section>

                <x-styleguide.section id="country" title="Country pages" lead="Every country calculator is built from the same pieces: a heading on one grid with the content below, a rate reference that dates each rate, and common amounts that link their calculations.">
                    <x-styleguide.specimen example="country-heading" />
                    <x-styleguide.specimen example="country-reference" />
                    <x-styleguide.specimen example="country-common-amounts" />
                </x-styleguide.section>

                <x-styleguide.section id="tiles" title="Tiles and rows" lead="Stat tiles summarise, link cards lead to tools, and list rows keep dates, names and figures in fixed columns.">
                    <x-styleguide.specimen example="pattern-stat-tiles" />
                    <x-styleguide.specimen example="pattern-feature-cards" />
                    <x-styleguide.specimen example="pattern-list-rows" />
                </x-styleguide.section>

                <x-styleguide.section id="states" title="States" lead="Every async control has a loading state, every list an empty state with a next step, and every error says what happened and what to do.">
                    <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
                        <x-styleguide.specimen example="pattern-empty" />
                        <x-styleguide.specimen example="pattern-loading" />
                    </div>
                    <x-styleguide.specimen example="pattern-alerts" />
                </x-styleguide.section>

                <x-styleguide.section id="calls-to-action" title="Calls to action" lead="A flat graphite band with a white key and an outlined one, the only place white text appears outside code. Use one per page, after the content it follows from.">
                    <x-styleguide.specimen example="pattern-cta" />
                </x-styleguide.section>

                {{-- Resources --}}
                <x-styleguide.section id="storybook" title="Storybook" lead="Every example on this page is also a Storybook story, rendered from the same Blade file, so designers and developers can browse, theme-switch and audit components in isolation without the two ever drifting apart.">
                    <p>
                        <a href="{{ url('/storybook/') }}" class="app-button-secondary">
                            <x-ui.icon name="puzzle" class="size-4" />
                            Open Storybook
                        </a>
                    </p>
                    <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
                        <div class="app-surface p-5 sm:p-6">
                            <h3 class="text-lg font-semibold text-ink">How the stories are made</h3>
                            <ol class="mt-4 space-y-3">
                                @foreach([
                                    'Each example in <code>resources/views/styleguide/examples</code> is described once in <code>ComponentCatalog</code>: its section, title, description and canvas.',
                                    '<code>php artisan design-system:storybook</code> renders every example through Blade and writes one story file per section, plus the Design Tokens for the foundation pages.',
                                    'Storybook '.Styleguide::STORYBOOK_VERSION.' loads the production stylesheet and the site\'s Alpine components, with a light and dark switch and axe-core on every story.',
                                    'The test suite fails while the generated stories are out of date, so a change to an example, a component or the stylesheet always reaches Storybook.',
                                ] as $step)
                                    <li class="flex gap-3 text-sm leading-6 text-ink-muted">
                                        <span class="tabular flex size-6 shrink-0 items-center justify-center rounded-full bg-action-soft text-xs font-semibold text-action-deep" aria-hidden="true">{{ $loop->iteration }}</span>
                                        <span class="{{ $inlineCode }}">{!! $step !!}</span>
                                    </li>
                                @endforeach
                            </ol>
                        </div>
                        <x-styleguide.specimen
                            title="Run it"
                            description="Regenerate after changing an example or app.css; every deploy also publishes it at /storybook"
                            language="Shell"
                            :code="'yarn install'.PHP_EOL.'php artisan design-system:storybook'.PHP_EOL.'yarn storybook'.PHP_EOL.'yarn build-storybook'"
                        />
                    </div>
                </x-styleguide.section>

                <x-styleguide.section id="agents" title="For AI agents" lead="Everything on this page is also available in machine-readable form, generated from the same files the site is built from.">
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div class="app-surface flex flex-col p-5">
                            <p class="flex items-center gap-2">
                                <span class="flex size-9 items-center justify-center rounded-control bg-action-soft text-action" aria-hidden="true"><x-ui.icon name="braces" class="size-4" /></span>
                                <span class="font-semibold text-ink">Design tokens</span>
                            </p>
                            <p class="mt-3 text-sm leading-6 text-ink-muted">Colour, typography, radius, shadow, easing, duration and size tokens in the Design Tokens Community Group format 2025.10, with OKLCH values, hex fallbacks and dark-theme values under <code class="font-mono text-xs text-ink">$extensions</code>.</p>
                            <code class="mt-4 block break-all rounded-control border border-line bg-surface-subtle px-3 py-2 font-mono text-xs text-ink">{{ $tokensUrl }}</code>
                            <div class="mt-auto flex flex-wrap gap-2 pt-4">
                                <a href="{{ $tokensUrl }}" download="eu-vat-info.tokens.json" class="app-button-secondary h-10 min-h-10 px-4"><x-ui.icon name="download" class="size-4" />Download</a>
                                <button type="button" class="app-button-ghost h-10 min-h-10" x-data x-on:click="$copy(@js($tokensUrl), 'Tokens URL copied')"><x-ui.icon name="copy" class="size-4" />Copy URL</button>
                            </div>
                        </div>
                        <div class="app-surface flex flex-col p-5">
                            <p class="flex items-center gap-2">
                                <span class="flex size-9 items-center justify-center rounded-control bg-action-soft text-action" aria-hidden="true"><x-ui.icon name="file-text" class="size-4" /></span>
                                <span class="font-semibold text-ink">DESIGN.md</span>
                            </p>
                            <p class="mt-3 text-sm leading-6 text-ink-muted">The complete guide as Markdown: principles, every token and component recipe, patterns, accessibility rules and the checks the build enforces. Give it to your coding agent before it touches a template.</p>
                            <code class="mt-4 block break-all rounded-control border border-line bg-surface-subtle px-3 py-2 font-mono text-xs text-ink">{{ $markdownUrl }}</code>
                            <div class="mt-auto flex flex-wrap gap-2 pt-4">
                                <a href="{{ $markdownUrl }}" class="app-button-secondary h-10 min-h-10 px-4"><x-ui.icon name="arrow-right" class="size-4" />Open</a>
                                <button type="button" class="app-button-ghost h-10 min-h-10" x-data x-on:click="$copy(@js($markdownUrl), 'Guide URL copied')"><x-ui.icon name="copy" class="size-4" />Copy URL</button>
                            </div>
                        </div>
                    </div>

                    <x-styleguide.specimen
                        title="Fetch both from a script"
                        description="Plain HTTP, no key, CORS enabled"
                        language="Shell"
                        :code="'curl -s '.$tokensUrl.' -o eu-vat-info.tokens.json'.PHP_EOL.'curl -s '.$markdownUrl.' -o DESIGN.md'"
                    />

                    <div class="app-surface p-5 sm:p-6">
                        <h3 class="text-lg font-semibold text-ink">Quick start for agents</h3>
                        <ol class="mt-4 space-y-3">
                            @foreach($guide->numberedList('Quick start for agents') as $step)
                                <li class="flex gap-3 text-sm leading-6 text-ink-muted">
                                    <span class="tabular flex size-6 shrink-0 items-center justify-center rounded-full bg-action-soft text-xs font-semibold text-action-deep" aria-hidden="true">{{ $loop->iteration }}</span>
                                    <span class="{{ $inlineCode }} [&_strong]:font-semibold [&_strong]:text-ink">{!! $markdown($step) !!}</span>
                                </li>
                            @endforeach
                        </ol>
                    </div>
                </x-styleguide.section>

                <x-styleguide.section id="rules" title="Rules and checks" lead="The test suite fails the build when a template breaks one of these rules, naming the file, the line and the fix.">
                    <div class="app-surface p-5 sm:p-6">
                        <h3 class="text-lg font-semibold text-ink">The build fails when a template</h3>
                        <ol class="mt-4 grid grid-cols-1 gap-x-8 gap-y-2.5 md:grid-cols-2">
                            @foreach($templateRules as $rule)
                                <li class="flex gap-3 text-sm leading-6 text-ink-muted">
                                    <x-ui.icon name="shield-check" class="mt-1 size-4 text-success" />
                                    <span class="{{ $inlineCode }}">{!! $markdown($rule) !!}</span>
                                </li>
                            @endforeach
                        </ol>
                        @foreach($documentationRules as $rule)
                            <p class="mt-5 flex gap-3 border-t border-line pt-5 text-sm leading-6 text-ink-muted">
                                <x-ui.icon name="file-text" class="mt-1 size-4 text-action" />
                                <span class="{{ $inlineCode }}">{!! $markdown('It also fails when '.$rule.'.') !!}</span>
                            </p>
                        @endforeach
                    </div>

                    <div class="app-surface overflow-hidden">
                        <h3 class="sr-only">Do and don't</h3>
                        <div class="hidden grid-cols-2 gap-6 bg-surface-subtle px-5 py-2.5 font-display text-[0.8125rem] font-medium sm:grid" aria-hidden="true">
                            <span class="inline-flex items-center gap-1.5 text-success"><x-ui.icon name="check" class="size-3.5" />Do</span>
                            <span class="inline-flex items-center gap-1.5 text-danger"><x-ui.icon name="x" class="size-3.5" />Don't</span>
                        </div>
                        <ul class="divide-y divide-line border-line text-sm leading-6 sm:border-t [&_code]:rounded-xs [&_code]:bg-surface-muted [&_code]:px-1 [&_code]:font-mono [&_code]:text-xs [&_code]:text-ink">
                            @foreach($guide->table("Do and don't") as $row)
                                <li class="grid grid-cols-1 gap-1.5 px-5 py-3 sm:grid-cols-2 sm:gap-6">
                                    <p class="flex gap-2 text-ink">
                                        <x-ui.icon name="check" class="mt-1 size-4 text-success sm:hidden" />
                                        <span><span class="sr-only">Do: </span>{!! $markdown($row['Do'] ?? '') !!}</span>
                                    </p>
                                    <p class="flex gap-2 text-ink-muted">
                                        <x-ui.icon name="x" class="mt-1 size-4 text-danger sm:hidden" />
                                        <span><span class="sr-only">Don't: </span>{!! $markdown($row["Don't"] ?? '') !!}</span>
                                    </p>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </x-styleguide.section>
            </div>
        </div>

        <section class="hero-canvas mt-20 rounded-panel" aria-labelledby="businesspress-title" lang="en">
            <x-hero-backdrop />
            <div class="relative grid grid-cols-1 gap-8 p-6 sm:p-10 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-center">
                <div class="max-w-2xl">
                    <h2 id="businesspress-title" class="text-2xl font-semibold tracking-[-0.02em] text-white sm:text-3xl">Get a design system like this for your business</h2>
                    <p class="mt-3 text-base leading-7 text-white/80">Tokens, accessible components and a living styleguide that your team and your AI agents can build with, set up for your brand on BusinessPress.</p>
                </div>
                <a href="{{ Styleguide::BUSINESSPRESS_URL }}" target="_blank" rel="noopener" class="app-button-inverse shrink-0 px-6">
                    Get yours on BusinessPress
                    <x-ui.icon name="arrow-up-right" class="size-4" />
                <x-ui.new-tab /></a>
            </div>
        </section>
    </div>
</div>
