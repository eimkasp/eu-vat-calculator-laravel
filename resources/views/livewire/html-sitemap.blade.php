@section('seo')
    <x-seo-meta :title="__('ui.sitemap.meta_title')" :description="__('ui.sitemap.meta_desc')" :url="url(locale_path('/sitemap'))">
        <x-json-ld :data="[
            '@type' => 'CollectionPage',
            'name' => __('ui.sitemap.schema_name'),
            'description' => __('ui.sitemap.schema_desc'),
            'url' => url(locale_path('/sitemap')),
            'isPartOf' => ['@type' => 'WebSite', 'name' => __('ui.site_name'), 'url' => url(locale_path('/'))],
        ]" />
    </x-seo-meta>
@endsection

@php
    $sections = [
        'pages' => [
            'icon' => 'home',
            'title' => __('ui.sitemap.main_pages'),
            'links' => [
                [__('ui.sitemap.home_all'), locale_path('/'), __('ui.sitemap.home_desc')],
                [__('ui.sitemap.calculator'), locale_path('/vat-calculator'), __('ui.sitemap.calculator_desc')],
                [__('ui.nav.vat_number_validator'), locale_path('/vat-number-validator'), __('ui.nav.validator_desc')],
                [__('ui.sitemap.map'), locale_path('/vat-map'), __('ui.sitemap.map_desc')],
                [__('ui.sitemap.history'), locale_path('/vat-changes'), __('ui.sitemap.history_desc')],
                [__('ui.sitemap.top_calculations'), locale_path('/top-vat-calculations'), __('ui.sitemap.top_calculations_desc')],
                [__('ui.sitemap.embed'), route('widget.embed'), __('ui.sitemap.embed_desc')],
                [__('ui.nav.updates'), locale_path('/blog'), __('ui.blog.description')],
                ...collect($blogPosts)->map(fn (array $post) => [$post['title'], locale_path('/blog/'.$post['slug']), $post['description']])->all(),
                [__('ui.changelog.nav_label'), locale_path('/changelog'), __('ui.changelog.subheading')],
                ...collect(config('vat-scenarios', []))->map(fn (array $guide, string $slug) => [$guide['title'], locale_path('/vat-guides/'.$slug), $guide['summary']])->values()->all(),
            ],
        ],
        'developers' => [
            'icon' => 'code',
            'title' => __('ui.sitemap.api_data'),
            'links' => [
                [__('ui.nav.api'), locale_path('/vat-validation-api'), __('ui.nav.api_desc')],
                [__('ui.sitemap.openapi'), '/api/v1/openapi.json', __('ui.sitemap.openapi_desc')],
                [__('ui.nav.dataset'), locale_path('/datasets/eu-vat-rates'), __('ui.nav.dataset_desc')],
                [__('ui.sitemap.mcp_server'), locale_path('/mcp-server'), __('ui.sitemap.mcp_server_desc')],
                [__('ui.styleguide.nav_label'), locale_path('/styleguide'), __('ui.styleguide.nav_desc')],
                [__('ui.brand.nav_label'), locale_path('/brand'), __('ui.brand.nav_desc')],
                [__('ui.sitemap.llms_txt'), '/llms.txt', __('ui.sitemap.llms_txt_desc')],
                [__('ui.sitemap.full_vat_rates'), '/llms-full.txt', __('ui.sitemap.full_vat_rates_desc')],
                [__('ui.sitemap.json_api'), '/api/llm/vat-rates', __('ui.sitemap.json_api_desc')],
                [__('ui.sitemap.xml_sitemap'), '/sitemap.xml', __('ui.sitemap.xml_sitemap_desc')],
            ],
        ],
        'external' => [
            'icon' => 'globe',
            'title' => __('ui.sitemap.external_resources'),
            'links' => [
                [__('ui.sitemap.vies_validation'), 'https://ec.europa.eu/taxation_customs/vies/', __('ui.sitemap.vies_desc')],
                [__('ui.sitemap.europe_guide'), 'https://europa.eu/youreurope/business/taxation/vat/', __('ui.sitemap.europe_guide_desc')],
                [__('ui.sitemap.github_repo'), 'https://github.com/eimkasp/eu-vat.info', __('ui.sitemap.github_desc')],
                [__('ui.sitemap.pdf_tools'), 'https://pdfcheck.online/', __('ui.sitemap.pdf_tools_desc')],
            ],
        ],
    ];
@endphp

<div>
    <x-page-header :title="__('ui.sitemap.title')" :description="__('ui.sitemap.subtitle')" :breadcrumbs="[__('ui.breadcrumbs.sitemap') => '']" />

    <div class="app-container space-y-12 py-8 sm:py-10">
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2 lg:items-start">
            @foreach([Arr::only($sections, ['pages']), Arr::only($sections, ['developers', 'external'])] as $column)
                <div class="space-y-6">
                    @foreach($column as $key => $section)
                        <section class="app-surface p-5 sm:p-6" aria-labelledby="sitemap-{{ $key }}">
                            <h2 id="sitemap-{{ $key }}" class="flex items-center gap-2 text-lg font-semibold text-ink">
                                <x-ui.icon :name="$section['icon']" class="size-5 text-action" />
                                {{ $section['title'] }}
                            </h2>
                            <ul class="mt-4 space-y-4">
                                @foreach($section['links'] as [$label, $url, $description])
                                    @php($external = str_starts_with($url, 'http') && ! str_starts_with($url, url('/')))
                                    <li>
                                        <a href="{{ $url }}" @if($external) target="_blank" rel="noopener noreferrer" @endif class="font-semibold text-action hover:text-action-deep hover:underline">{{ Str::contains($label, ' ') ? Str::beforeLast($label, ' ').' ' : '' }}<span class="whitespace-nowrap">{{ Str::afterLast($label, ' ') }}<x-ui.icon :name="$external ? 'arrow-up-right' : 'chevron-right'" class="ml-1 inline size-3.5 align-[-2px]" /></span>@if($external)<x-ui.new-tab />@endif</a>
                                        <p class="mt-0.5 text-sm leading-6 text-ink-muted">{{ $description }}</p>
                                    </li>
                                @endforeach
                            </ul>
                        </section>
                    @endforeach
                </div>
            @endforeach
        </div>

        <section aria-labelledby="sitemap-countries">
            <div class="max-w-3xl">
                <h2 id="sitemap-countries" class="text-2xl font-semibold tracking-[-0.02em] text-ink">{{ __('ui.sitemap.all_country_pages') }}</h2>
                <p class="mt-2 text-base leading-7 text-ink-muted">{{ __('ui.sitemap.country_pages_desc') }}</p>
            </div>

            @foreach($groups as $group => $countries)
                @continue($countries->isEmpty())
                <h3 class="mt-8 text-lg font-semibold text-ink">{{ __('ui.calculator.groups.'.$group) }}</h3>
                <ul class="mt-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                    @foreach($countries as $country)
                        <li class="app-surface p-5">
                            <div class="flex items-center gap-3 border-b border-line pb-3">
                                <x-ui.flag :iso="$country->iso_code" size="lg" />
                                <div class="min-w-0">
                                    <h4 class="font-display text-base font-medium text-ink">{{ $country->name }}</h4>
                                    <p class="text-sm text-ink-muted">{{ __('ui.sitemap.standard_rate_label') }} <span class="tabular font-semibold text-ink">{{ \App\Support\Vat\Money::percent($country->standard_rate) }}</span></p>
                                </div>
                            </div>
                            <ul class="mt-3 space-y-1.5 text-sm">
                                <li>
                                    <a href="{{ locale_path('/vat-calculator/'.$country->slug) }}" class="inline-flex min-h-6 items-center gap-1.5 text-action hover:text-action-deep hover:underline">
                                        <x-ui.icon name="calculator" class="size-4 shrink-0 text-ink-quiet" />
                                        {{ __('ui.sitemap.country_calculator', ['country' => $country->name]) }}
                                    </a>
                                </li>
                                @if($country->is_eu_member && $country->vies_available)
                                    <li>
                                        <a href="{{ locale_path('/vat-number-validator/'.$country->slug) }}" class="inline-flex min-h-6 items-center gap-1.5 text-action hover:text-action-deep hover:underline">
                                            <x-ui.icon name="shield-check" class="size-4 shrink-0 text-ink-quiet" />
                                            {{ __('ui.sitemap.validate_numbers', ['country' => $country->name]) }}
                                        </a>
                                    </li>
                                @endif
                                @if($country->is_eu_member && $country->hasVatHistory())
                                    <li>
                                        <a href="{{ locale_path('/vat-rates/'.$country->slug.'/history') }}" class="inline-flex min-h-6 items-center gap-1.5 text-action hover:text-action-deep hover:underline">
                                            <x-ui.icon name="history" class="size-4 shrink-0 text-ink-quiet" />
                                            {{ __('ui.sitemap.country_history', ['country' => $country->name]) }}
                                        </a>
                                    </li>
                                @endif
                            </ul>
                        </li>
                    @endforeach
                </ul>
            @endforeach
        </section>

        <section class="app-surface p-5 sm:p-8" aria-labelledby="sitemap-about">
            <h2 id="sitemap-about" class="text-xl font-semibold text-ink">{{ __('ui.sitemap.about_title') }}</h2>
            <div class="app-prose mt-3 max-w-none">
                <p>
                    {!! __('ui.sitemap.about_p1', [
                        'calculator_link' => '<a href="'.e(locale_path('/vat-calculator')).'" class="app-link">'.e(__('ui.sitemap.calculator')).'</a>',
                        'map_link' => '<a href="'.e(locale_path('/vat-map')).'" class="app-link">'.e(__('ui.sitemap.map')).'</a>',
                    ]) !!}
                </p>
                <p>
                    {!! __('ui.sitemap.about_p2', [
                        'llms_link' => '<a href="/llms.txt" class="app-link">'.e(__('ui.sitemap.about_llms_label')).'</a>',
                        'api_link' => '<a href="/api/llm/vat-rates" class="app-link">'.e(__('ui.sitemap.about_api_label')).'</a>',
                    ]) !!}
                </p>
            </div>
        </section>
    </div>
</div>
