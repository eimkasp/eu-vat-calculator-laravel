@php
    $canonical = app(\App\Support\Seo\SeoPolicy::class)->canonicalHost().locale_path('/datasets/eu-vat-rates');
    $schema = [
        '@context' => 'https://schema.org',
        '@graph' => [
            \App\Support\Seo\DatasetSchema::rates($dateModified),
        ],
    ];
@endphp

@section('seo')
    <x-seo-meta
        title="EU VAT Rates Dataset — Current Rates, History and Downloads"
        description="Download current VAT rates for all 27 EU member states as CSV, JSON or Markdown, with source provenance and verification dates."
        :url="$canonical">
        <script type="application/ld+json">{!! json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
    </x-seo-meta>
@endsection

<div>
    <x-page-header
        title="EU VAT rates dataset"
        :description="'Current VAT rate data for '.$countries->count().' EU member states, available in formats suited to analysis, integrations and research.'"
        eyebrow="Open VAT data"
        :breadcrumbs="['EU VAT rates dataset' => '']"
    >
        <x-slot:actions>
            <span class="inline-flex items-center gap-2 rounded-control border border-line bg-surface-subtle px-3 py-1.5 text-sm text-ink-muted">
                <x-ui.icon name="clock" class="size-4" />
                <span>Last updated <time class="tabular" datetime="{{ $dateModified->toDateString() }}">{{ $dateModified->format('j M Y') }}</time></span>
            </span>
        </x-slot:actions>
    </x-page-header>

    <div class="app-container py-8 sm:py-10">
        <div class="grid grid-cols-1 items-start gap-8 lg:grid-cols-[minmax(0,1fr)_20rem]">
            <div class="min-w-0 space-y-8">
                <section class="app-surface overflow-hidden" aria-labelledby="dataset-downloads">
                    <h2 id="dataset-downloads" class="border-b border-line px-5 py-4 text-lg font-bold text-ink sm:px-6">Get the data</h2>
                    <ul class="divide-y divide-line">
                        @foreach([
                            ['CSV', 'Spreadsheet and analysis workflows', '/datasets/eu-vat-rates.csv', 'Download', 'download', 'table'],
                            ['JSON', 'Applications and data pipelines', '/datasets/eu-vat-rates.json', 'Open', 'arrow-right', 'braces'],
                            ['Markdown', 'Research and language-model context', '/llms-full.txt', 'Open', 'arrow-right', 'file-text'],
                            ['REST API', 'Live rates and calculations, one country at a time', locale_path('/vat-validation-api').'#rates-api', 'Read', 'arrow-right', 'code'],
                            ['MCP server', 'VAT tools for Claude, ChatGPT and other AI agents', locale_path('/mcp-server'), 'Connect', 'arrow-right', 'server'],
                        ] as [$format, $use, $href, $action, $actionIcon, $icon])
                            <li class="flex items-center gap-4 px-5 py-4 sm:px-6">
                                <span class="flex size-10 shrink-0 items-center justify-center rounded-control bg-action-soft text-action" aria-hidden="true">
                                    <x-ui.icon :name="$icon" class="size-5" />
                                </span>
                                <span class="min-w-0 flex-1">
                                    <span class="block font-semibold text-ink">{{ $format }}</span>
                                    <span class="block text-sm text-ink-muted">{{ $use }}</span>
                                </span>
                                <a href="{{ $href }}" class="app-button-secondary h-10 min-h-10 shrink-0 px-4" aria-label="{{ $action }}: {{ $format }}">
                                    {{ $action }}
                                    <x-ui.icon :name="$actionIcon" class="size-4" />
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </section>

                <section class="app-surface p-5 sm:p-6" aria-labelledby="dataset-contents">
                    <h2 id="dataset-contents" class="text-lg font-bold text-ink">What is included</h2>
                    <p class="mt-2 max-w-[68ch] text-base leading-7 text-ink-muted">Each row includes the country, ISO code, standard rate, available reduced-rate fields, currency and record update time. Historical records are published separately through each country’s VAT history.</p>
                    <p class="mt-4 flex flex-wrap gap-2">
                        @foreach(['country', 'iso_code', 'standard_rate', 'reduced_rate', 'super_reduced_rate', 'parking_rate', 'currency_code', 'last_updated'] as $column)
                            <code class="rounded-xs border border-line bg-surface-subtle px-1.5 py-0.5 font-mono text-xs text-ink-muted">{{ $column }}</code>
                        @endforeach
                    </p>
                </section>
            </div>

            <aside class="app-surface p-5 lg:sticky lg:top-24">
                <h2 class="text-base font-bold text-ink">Provenance</h2>
                <p class="mt-2 text-sm leading-6 text-ink-muted">Rates are aggregated from European Commission material and the open VAT Rates dataset, then checked by the project’s data-integrity workflow.</p>
                <dl class="mt-4 divide-y divide-line border-t border-line text-sm">
                    <div class="flex justify-between gap-4 py-2.5">
                        <dt class="text-ink-muted">Coverage</dt>
                        <dd class="font-semibold text-ink">European Union</dd>
                    </div>
                    <div class="flex justify-between gap-4 py-2.5">
                        <dt class="text-ink-muted">Member states</dt>
                        <dd class="tabular font-semibold text-ink">{{ $countries->count() }}</dd>
                    </div>
                    <div class="flex justify-between gap-4 py-2.5">
                        <dt class="text-ink-muted">License</dt>
                        <dd><a class="app-link font-semibold" href="https://creativecommons.org/licenses/by/4.0/">CC BY 4.0</a></dd>
                    </div>
                </dl>
            </aside>
        </div>
    </div>
</div>
