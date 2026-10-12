@extends('layouts.app')

@php
    $popular = rescue(fn () => \App\Models\Country::query()
        ->whereIn('slug', ['germany', 'france', 'italy', 'spain', 'netherlands', 'belgium', 'poland', 'austria', 'portugal', 'ireland', 'sweden', 'hungary'])
        ->orderBy('name')
        ->get(['name', 'slug', 'iso_code', 'standard_rate']), collect(), false);
    $tools = [
        ['calculator', __('ui.error_404.tool_calculator'), __('ui.error_404.tool_calculator_desc'), locale_path('/vat-calculator')],
        ['shield-check', __('ui.error_404.tool_validator'), __('ui.error_404.tool_validator_desc'), locale_path('/vat-number-validator')],
        ['map', __('ui.error_404.tool_map'), __('ui.error_404.tool_map_desc'), locale_path('/vat-map')],
        ['history', __('ui.error_404.tool_history'), __('ui.error_404.tool_history_desc'), locale_path('/vat-changes')],
    ];
    $resources = [
        ['code', __('ui.error_404.resource_api'), __('ui.error_404.resource_api_desc'), '/api/v1/countries'],
        ['sparkles', __('ui.error_404.resource_llms'), __('ui.error_404.resource_llms_desc'), '/llms.txt'],
        ['grid', __('ui.error_404.resource_sitemap'), __('ui.error_404.resource_sitemap_desc'), locale_path('/sitemap')],
        ['github', __('ui.error_404.resource_github'), __('ui.error_404.resource_github_desc'), 'https://github.com/eimkasp/eu-vat.info'],
    ];
@endphp

@section('seo')
    <x-seo-meta :title="__('ui.error_404.meta_title')" :description="__('ui.error_404.meta_desc')" :url="url()->current()" robots="noindex, follow" :canonical="false" />
@endsection

@section('content')
    <section class="hero-canvas">
        <x-hero-backdrop />
        <div class="app-container relative py-16 text-center sm:py-24">
            <p class="tabular text-7xl font-bold tracking-[-0.05em] text-white/90 sm:text-8xl">404</p>
            <h1 class="mt-4 text-3xl font-bold tracking-[-0.03em] text-white sm:text-4xl">{{ __('ui.error_404.heading') }}</h1>
            <p class="mx-auto mt-4 max-w-2xl text-base leading-7 text-white/80 sm:text-lg">{{ __('ui.error_404.message') }}</p>
            <div class="mt-8 flex flex-col justify-center gap-3 sm:flex-row">
                <a href="{{ locale_path('/') }}" class="app-button-inverse">
                    <x-ui.icon name="home" class="size-4" />
                    {{ __('ui.error_404.back') }}
                </a>
                <button type="button" x-data x-on:click="$store.palette.show()" class="app-button-on-brand">
                    <x-ui.icon name="search" class="size-4" />
                    {{ __('ui.nav.search_placeholder') }}
                </button>
            </div>
        </div>
    </section>

    <div class="app-container space-y-12 py-12 sm:py-14">
        <section aria-labelledby="error-tools">
            <h2 id="error-tools" class="text-lg font-bold text-ink">{{ __('ui.error_404.popular_tools') }}</h2>
            <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @foreach($tools as [$icon, $label, $description, $url])
                    <a href="{{ $url }}" class="group app-surface flex flex-col gap-3 p-5 transition-[border-color,box-shadow] hover:border-action/40 hover:shadow-workflow">
                        <span class="flex size-10 items-center justify-center rounded-card bg-action-soft text-action" aria-hidden="true"><x-ui.icon :name="$icon" class="size-5" /></span>
                        <span class="font-semibold text-ink group-hover:text-action">{{ $label }}</span>
                        <span class="text-sm leading-6 text-ink-muted">{{ $description }}</span>
                    </a>
                @endforeach
            </div>
        </section>

        @if($popular->isNotEmpty())
            <section aria-labelledby="error-countries">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <h2 id="error-countries" class="text-lg font-bold text-ink">{{ __('ui.error_404.popular_countries') }}</h2>
                    <a href="{{ locale_path('/') }}" class="app-link inline-flex items-center gap-1 text-sm">
                        {{ __('ui.error_404.all_countries_cta') }}
                        <x-ui.icon name="arrow-right" class="size-3.5" />
                    </a>
                </div>
                <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6">
                    @foreach($popular as $country)
                        <a href="{{ locale_path('/vat-calculator/'.$country->slug) }}" class="group app-surface flex items-center gap-3 p-3.5 transition-colors hover:border-action/40">
                            <x-ui.flag :iso="$country->iso_code" size="lg" />
                            <span class="min-w-0">
                                <span class="block truncate text-sm font-semibold text-ink group-hover:text-action">{{ $country->name }}</span>
                                <span class="tabular block text-xs text-ink-muted">{{ \App\Models\Country::formatRate($country->standard_rate) }}%</span>
                            </span>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif

        <section aria-labelledby="error-resources">
            <h2 id="error-resources" class="text-lg font-bold text-ink">{{ __('ui.error_404.explore_resources') }}</h2>
            <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @foreach($resources as [$icon, $label, $description, $url])
                    @php($external = str_starts_with($url, 'http'))
                    <a href="{{ $url }}" @if($external) target="_blank" rel="noopener noreferrer" @endif class="group app-surface flex items-start gap-3 p-4 transition-colors hover:border-action/40">
                        <span class="flex size-9 shrink-0 items-center justify-center rounded-control bg-surface-muted text-ink-muted group-hover:text-action" aria-hidden="true"><x-ui.icon :name="$icon" class="size-4" /></span>
                        <span class="min-w-0">
                            <span class="block text-sm font-semibold text-ink group-hover:text-action">{{ $label }}</span>
                            <span class="mt-0.5 block text-xs leading-5 text-ink-muted">{{ $description }}</span>
                        </span>
                    @if($external)<x-ui.new-tab />@endif</a>
                @endforeach
            </div>
        </section>
    </div>
@endsection
