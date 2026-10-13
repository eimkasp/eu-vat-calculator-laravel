@use('App\Support\SiteNavigation')

@php
    $featured = [
        [
            'url' => locale_path('/vat-calculator'),
            'icon' => 'calculator',
            'title' => __('ui.tools.calculator_heading'),
            'badge' => __('ui.tools.most_popular'),
            'description' => __('ui.tools.calculator_description'),
            'features' => [__('ui.tools.calc_feature_1'), __('ui.tools.calc_feature_2'), __('ui.tools.calc_feature_3')],
            'cta' => __('ui.tools.open_calculator'),
        ],
        [
            'url' => locale_path('/vat-number-validator'),
            'icon' => 'shield-check',
            'title' => __('ui.nav.vat_number_validator'),
            'badge' => __('ui.tools.vies_powered'),
            'description' => __('ui.tools.validator_description'),
            'features' => [__('ui.tools.validator_feature_1'), __('ui.tools.validator_feature_2'), __('ui.tools.validator_feature_3')],
            'cta' => __('ui.tools.validate_number'),
        ],
    ];

    $more = collect(SiteNavigation::tools())
        ->reject(fn (array $tool) => $tool['icon'] === 'shield-check')
        ->map(fn (array $tool) => ['url' => $tool['url'], 'icon' => $tool['icon'], 'title' => $tool['label'], 'description' => $tool['description']])
        ->merge([
            ['url' => locale_path('/vat-changes'), 'icon' => 'history', 'title' => __('ui.nav.vat_history'), 'description' => __('ui.nav.history_desc')],
            ['url' => locale_path('/chrome-extension'), 'icon' => 'puzzle', 'title' => __('ui.footer.chrome_extension'), 'description' => __('ui.tools.extension_desc')],
        ]);
@endphp

@section('seo')
    <x-seo-meta :title="__('ui.tools.seo_title')" :description="__('ui.tools.seo_description')" :url="url()->current()">
        <x-json-ld :data="[
            '@type' => 'ItemList',
            'name' => __('ui.tools.heading'),
            'itemListElement' => collect($featured)->concat($more)->values()->map(fn (array $tool, int $index) => [
                '@type' => 'ListItem',
                'position' => $index + 1,
                'name' => $tool['title'],
                'url' => url($tool['url']),
            ])->all(),
        ]" />
    </x-seo-meta>
@endsection

<div>
    <x-page-header :title="__('ui.tools.heading')" :description="__('ui.tools.intro_text')" :eyebrow="__('ui.tools.eyebrow')" :breadcrumbs="[__('ui.breadcrumbs.tools') => '']" />

    <div class="app-container space-y-12 py-10 sm:py-12">
        <section aria-labelledby="core-tools">
            <h2 id="core-tools" class="app-eyebrow">{{ __('ui.tools.core_heading') }}</h2>
            <div class="mt-4 grid gap-5 md:grid-cols-2">
                @foreach($featured as $tool)
                    <a href="{{ $tool['url'] }}" class="group app-surface flex flex-col p-6 transition-[border-color,box-shadow] duration-200 hover:border-action/40 hover:shadow-workflow sm:p-7">
                        <div class="flex items-start gap-4">
                            <span class="flex size-12 shrink-0 items-center justify-center rounded-card bg-action-soft text-action" aria-hidden="true">
                                <x-ui.icon :name="$tool['icon']" class="size-6" />
                            </span>
                            <div class="min-w-0">
                                <h3 class="text-xl font-bold text-ink group-hover:text-action">{{ $tool['title'] }}</h3>
                                <span class="app-badge mt-1 bg-surface-muted text-ink-muted">{{ $tool['badge'] }}</span>
                            </div>
                        </div>
                        <p class="mt-4 text-sm leading-6 text-ink-muted">{{ $tool['description'] }}</p>
                        <ul class="mt-4 space-y-2">
                            @foreach($tool['features'] as $feature)
                                <li class="flex items-center gap-2 text-sm text-ink"><x-ui.icon name="check" class="size-4 text-success" />{{ $feature }}</li>
                            @endforeach
                        </ul>
                        <span class="mt-auto inline-flex items-center gap-1.5 pt-6 text-sm font-semibold text-action">
                            {{ $tool['cta'] }}
                            <x-ui.icon name="arrow-right" class="size-4 transition-transform duration-150 group-hover:translate-x-0.5" />
                        </span>
                    </a>
                @endforeach
            </div>
        </section>

        <section aria-labelledby="more-tools">
            <h2 id="more-tools" class="app-eyebrow">{{ __('ui.tools.more_heading') }}</h2>
            <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($more as $tool)
                    <a href="{{ $tool['url'] }}" class="group app-surface flex h-full gap-4 p-5 transition-[border-color,box-shadow] duration-200 hover:border-action/40 hover:shadow-workflow">
                        <span class="flex size-10 shrink-0 items-center justify-center rounded-card bg-surface-muted text-ink-muted transition-colors group-hover:bg-action-soft group-hover:text-action" aria-hidden="true">
                            <x-ui.icon :name="$tool['icon']" class="size-5" />
                        </span>
                        <span class="min-w-0">
                            <span class="flex items-center gap-1.5 font-semibold text-ink group-hover:text-action">
                                {{ $tool['title'] }}
                                <x-ui.icon name="arrow-right" class="size-3.5 opacity-0 transition-opacity group-hover:opacity-100" />
                            </span>
                            <span class="mt-1 block text-sm leading-6 text-ink-muted">{{ $tool['description'] }}</span>
                        </span>
                    </a>
                @endforeach
            </div>
        </section>

        <section aria-labelledby="vat-guides">
            <h2 id="vat-guides" class="app-eyebrow">{{ __('ui.tools.guides_heading') }}</h2>
            <ul class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3" lang="en">
                @foreach(config('vat-scenarios', []) as $slug => $guide)
                    <li>
                        <a href="{{ locale_path('/vat-guides/'.$slug) }}" class="group app-surface flex h-full flex-col p-5 transition-[border-color,box-shadow] duration-200 hover:border-action/40 hover:shadow-workflow">
                            <span class="font-semibold text-ink group-hover:text-action">{{ $guide['title'] }}</span>
                            <span class="mt-1 block text-sm leading-6 text-ink-muted">{{ $guide['summary'] }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </section>

        <section class="hero-canvas rounded-panel" aria-labelledby="api-cta">
            <div class="relative flex flex-col gap-5 p-6 sm:p-8 md:flex-row md:items-center md:justify-between">
                <div>
                    <h2 id="api-cta" class="text-xl font-bold text-white">{{ __('ui.tools.cta_title') }}</h2>
                    <p class="mt-1.5 max-w-xl text-sm leading-6 text-white/80">{{ __('ui.tools.cta_desc') }}</p>
                </div>
                <div class="flex shrink-0 flex-wrap gap-3">
                    <a href="{{ locale_path('/vat-validation-api') }}#rates-api" class="app-button-inverse">
                        <x-ui.icon name="code" class="size-4" />
                        {{ __('ui.tools.cta_api') }}
                    </a>
                    <a href="{{ locale_path('/datasets/eu-vat-rates') }}" class="app-button-on-brand">
                        <x-ui.icon name="database" class="size-4" />
                        {{ __('ui.nav.dataset') }}
                    </a>
                </div>
            </div>
        </section>
    </div>
</div>
