@use('App\Models\Country')
@use('App\Support\SiteNavigation')
@use('App\Support\Vat\VatNumberFormat')

@php
    $title = $pageCountry
        ? __('ui.vies_page.country_h1', ['country' => $pageCountry->name])
        : __('ui.vies_page.h1');
    $inVies = $pageCountry && collect($this->countries)->contains('slug', $pageCountry->slug);
    $subtitle = match (true) {
        $inVies => __('ui.vies_page.country_subtitle', ['country' => $pageCountry->name, 'code' => $pageCountry->iso_code]),
        (bool) $pageCountry => __('ui.vies_page.not_in_vies_desc', ['country' => $pageCountry->name]),
        default => __('ui.vies_page.subtitle'),
    };
    $breadcrumbs = $pageCountry
        ? [__('ui.vies_page.nav_title') => locale_path('/vat-number-validator'), $pageCountry->name => '']
        : [__('ui.vies_page.nav_title') => ''];
    $faqs = collect(range(1, 5))->map(fn (int $i) => ['q' => __("ui.vies_page.faq_q{$i}"), 'a' => __("ui.vies_page.faq_a{$i}")]);
    $prefixes = collect($this->countries)->mapWithKeys(fn (array $c) => [$c['prefix'] => $c['iso']])->put('GR', 'GR')->all();
    $format = $inVies ? config('vat-number-formats.'.strtoupper((string) $pageCountry->iso_code)) : null;
    $endpoint = url('/api/vat/validation/validate');
    $relatedTools = collect(SiteNavigation::tools())->reject(fn (array $tool) => $tool['active'])->take(4);
    $sources = [
        'live' => ['globe', __('ui.vies_page.source_live'), 'text-ink'],
        'recent' => ['zap', __('ui.vies_page.source_recent'), 'text-ink'],
        'fallback' => ['alert-triangle', __('ui.vies_page.source_fallback'), 'text-warning'],
    ];
@endphp

@section('seo')
    <x-seo-meta
        :title="$pageCountry ? __('ui.vies_page.country_title', ['country' => $pageCountry->name]) : __('ui.vies_page.title')"
        :description="$pageCountry ? __('ui.vies_page.country_description', ['country' => $pageCountry->name, 'code' => $pageCountry->iso_code]) : __('ui.vies_page.description')"
        type="website"
    >
        <x-json-ld :data="[
            '@type' => 'WebApplication',
            'name' => $title,
            'description' => $subtitle,
            'url' => url()->current(),
            'applicationCategory' => 'BusinessApplication',
            'operatingSystem' => 'All',
            'inLanguage' => app()->getLocale(),
            'offers' => ['@type' => 'Offer', 'price' => '0', 'priceCurrency' => 'EUR'],
        ]" />
        <x-json-ld :data="[
            '@type' => 'FAQPage',
            'mainEntity' => $faqs->map(fn (array $faq) => [
                '@type' => 'Question',
                'name' => $faq['q'],
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => $faq['a']],
            ])->all(),
        ]" />
    </x-seo-meta>
@endsection

<div>
    <section class="hero-canvas">
        <x-hero-backdrop />
        <div class="app-container relative pb-12 pt-6 sm:pb-16 sm:pt-8">
            <x-site-breadcrumbs variant="dark" :items="$breadcrumbs" />

            <div class="mx-auto mb-8 mt-6 max-w-3xl text-center">
                @if($pageCountry)
                    <x-ui.flag :iso="$pageCountry->iso_code" size="lg" :lazy="false" class="mx-auto mb-4" />
                @endif
                <h1 class="text-3xl font-bold tracking-[-0.03em] text-white sm:text-5xl sm:leading-[1.08]">{{ $title }}</h1>
                <p class="mx-auto mt-4 max-w-2xl text-base leading-7 text-white/80 sm:text-lg">{{ $subtitle }}</p>
            </div>

            <div id="validator" class="app-surface-raised mx-auto max-w-4xl scroll-mt-24 overflow-hidden text-ink" x-data="viesValidator(@js([
                'prefixes' => $prefixes,
                'flagBase' => asset('images/flags'),
                'formats' => VatNumberFormat::forBrowser(),
                'names' => collect($this->countries)->pluck('name', 'prefix')->all(),
                'mismatchTemplate' => __('ui.vies_page.format_mismatch'),
            ]))">
                @if($pageCountry && ! $inVies)
                    <div class="flex items-start gap-3 border-b border-line bg-warning-soft px-5 py-4 text-sm sm:px-7" role="note">
                        <x-ui.icon name="info" class="mt-0.5 size-5 text-warning" />
                        <div>
                            <p class="font-semibold text-ink">{{ __('ui.vies_page.not_in_vies_title', ['country' => $pageCountry->name]) }}</p>
                            @if($pageCountry->isCalculatorAvailable())
                                <a href="{{ locale_path('/vat-calculator/'.$pageCountry->slug) }}" class="app-link mt-1 inline-flex items-center gap-1">
                                    {{ __('ui.vies_page.country_calculator_link', ['country' => $pageCountry->name]) }}
                                    <x-ui.icon name="arrow-right" class="size-3.5" />
                                </a>
                            @endif
                        </div>
                    </div>
                @endif
                <form wire:submit="validateVat" class="p-5 sm:p-7" novalidate>
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-[minmax(0,1.45fr)_minmax(0,1fr)_auto] md:items-end">
                        <div>
                            <label for="vat-number" class="mb-1.5 block text-[0.8125rem] font-semibold text-ink-muted">{{ __('ui.vies_page.vat_number_label') }}</label>
                            <input
                                id="vat-number"
                                wire:model="vat_number"
                                x-on:input="detect($event.target.value)"
                                x-on:blur="checkFormat($event.target.value)"
                                type="text"
                                autocomplete="off"
                                autocapitalize="characters"
                                spellcheck="false"
                                maxlength="40"
                                enterkeyhint="search"
                                placeholder="{{ __('ui.vies_page.vat_placeholder') }}"
                                class="app-field h-12 font-mono text-base tracking-wide uppercase placeholder:font-sans placeholder:normal-case placeholder:tracking-normal"
                                aria-required="true"
                                aria-describedby="{{ $errors->has('vat_number') ? 'vat-number-error ' : '' }}vat-number-hint vat-number-format-hint"
                                @error('vat_number') aria-invalid="true" @enderror
                            >
                        </div>

                        <div>
                            <label for="vat-country" class="mb-1.5 flex items-center justify-between gap-2 text-[0.8125rem] font-semibold text-ink-muted">
                                {{ __('ui.vies_page.country_label') }}
                                <span x-cloak x-show="detected" x-transition.opacity class="inline-flex items-center gap-1 text-xs font-medium text-success">
                                    <x-ui.icon name="check" class="size-3.5" />
                                    {{ __('ui.vies_page.detected_country') }}
                                </span>
                            </label>
                            <div class="relative">
                                <template x-if="flag">
                                    <img :src="flag" alt="" width="22" height="16" class="app-flag pointer-events-none absolute top-1/2 left-3.5 h-4 w-[1.375rem] -translate-y-1/2">
                                </template>
                                <select
                                    id="vat-country"
                                    wire:model="country_code"
                                    x-on:change="detected = false"
                                    class="app-select h-12"
                                    :class="flag ? 'pl-11' : ''"
                                    @error('country_code') aria-invalid="true" aria-describedby="vat-country-error" @enderror
                                >
                                    <option value="">{{ __('ui.vies_page.select_country') }}</option>
                                    @foreach($this->countries as $option)
                                        <option value="{{ $option['iso'] }}">{{ $option['name'] }} ({{ $option['prefix'] }})</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <button type="submit" class="app-button-primary h-12 w-full md:w-auto md:px-6" wire:loading.attr="disabled" wire:target="validateVat,prefillExample">
                            <span wire:loading.remove wire:target="validateVat,prefillExample" class="inline-flex"><x-ui.icon name="shield-check" class="size-4" /></span>
                            <span wire:loading wire:target="validateVat,prefillExample" class="size-4 animate-spin rounded-full border-2 border-white/40 border-t-white" aria-hidden="true"></span>
                            {{ __('ui.vies_page.validate_btn') }}
                        </button>
                    </div>

                    @error('vat_number')
                        <p id="vat-number-error" class="mt-3 flex items-center gap-2 text-sm font-medium text-danger" role="alert"><x-ui.icon name="alert-triangle" class="size-4" />{{ $message }}</p>
                    @enderror
                    @error('country_code')
                        <p id="vat-country-error" class="mt-3 flex items-center gap-2 text-sm font-medium text-danger" role="alert"><x-ui.icon name="alert-triangle" class="size-4" />{{ $message }}</p>
                    @enderror

                    <p id="vat-number-format-hint" class="mt-3 flex items-start gap-2 text-sm font-medium text-warning" x-cloak x-show="mismatch" role="status">
                        <x-ui.icon name="alert-triangle" class="mt-0.5 size-4 shrink-0" />
                        <span x-text="mismatch"></span>
                    </p>
                    <p id="vat-number-hint" class="mt-3 text-sm text-ink-muted">
                        {{ __('ui.vies_page.auto_detect_hint') }}
                        <button type="button" wire:click="prefillExample" class="app-link font-semibold">{{ __('ui.vies_page.try_example') }}</button>
                    </p>
                </form>

                <div wire:loading.class="opacity-60" wire:target="validateVat,prefillExample" class="transition-opacity">
                    <p class="sr-only" role="status">
                        <span wire:loading wire:target="validateVat,prefillExample">{{ __('ui.vies_page.validating') }}</span>
                        @if(! $error && $result)
                            <span wire:loading.remove wire:target="validateVat,prefillExample">{{ $result['valid'] ? __('ui.vies_page.result_valid') : __('ui.vies_page.result_invalid') }}: {{ $result['prefix'].$result['vat_number'] }}</span>
                        @endif
                    </p>

                    @if($error)
                        <div class="flex items-start gap-3 border-t border-line bg-danger-soft px-5 py-4 text-sm text-ink sm:px-7" role="alert">
                            <x-ui.icon name="alert-triangle" class="mt-0.5 size-5 text-danger" />
                            <p class="leading-6">{{ $error }}</p>
                        </div>
                    @elseif($result)
                        @php
                            $resultCountry = collect($this->countries)->firstWhere('iso', $result['country_code']);
                            $fullNumber = $result['prefix'].$result['vat_number'];
                            [$sourceIcon, $sourceLabel, $sourceTone] = $sources[$result['source']] ?? $sources['live'];
                        @endphp
                        <div wire:key="result-{{ $fullNumber }}" class="border-t border-line" data-validation-result="{{ $result['valid'] ? 'valid' : 'invalid' }}">
                            <div @class(['flex flex-wrap items-start gap-3 px-5 py-4 sm:px-7', 'bg-success-soft' => $result['valid'], 'bg-danger-soft' => ! $result['valid']])>
                                <x-ui.icon :name="$result['valid'] ? 'check-circle' : 'x-circle'" :class="$result['valid'] ? 'mt-0.5 size-6 text-success' : 'mt-0.5 size-6 text-danger'" />
                                <div class="min-w-0 flex-1">
                                    <h2 class="text-lg font-bold text-ink">{{ $result['valid'] ? __('ui.vies_page.result_valid') : __('ui.vies_page.result_invalid') }}</h2>
                                    <p class="mt-0.5 max-w-[60ch] text-sm leading-6 text-ink-muted">
                                        {{ $result['valid'] ? __('ui.vies_page.result_valid_desc', ['country' => $resultCountry['name'] ?? $result['country_code']]) : __('ui.vies_page.result_invalid_desc') }}
                                    </p>
                                </div>
                                <button type="button" x-on:click="$copy(@js($fullNumber), @js(__('ui.vies_page.copied')))" class="app-button-secondary h-9 min-h-9 px-3 text-sm" aria-label="{{ __('ui.vies_page.copy_number') }}">
                                    <x-ui.icon name="copy" class="size-4" />
                                    {{ __('ui.calculator.copy') }}
                                </button>
                            </div>

                            <dl class="grid gap-x-8 px-5 py-2 sm:grid-cols-2 sm:px-7">
                                <div class="border-b border-line py-3">
                                    <dt class="text-xs font-semibold text-ink-muted">{{ __('ui.vies_page.vat_number_label') }}</dt>
                                    <dd class="mt-1 font-mono text-base font-semibold tracking-wide text-ink">{{ $fullNumber }}</dd>
                                </div>
                                <div class="border-b border-line py-3">
                                    <dt class="text-xs font-semibold text-ink-muted">{{ __('ui.vies_page.country_label') }}</dt>
                                    <dd class="mt-1 font-semibold text-ink">
                                        @if($resultCountry)
                                            <a href="{{ locale_path('/vat-calculator/'.$resultCountry['slug']) }}" class="inline-flex items-center gap-2 hover:text-action">
                                                <x-ui.flag :iso="$resultCountry['iso']" size="sm" />
                                                {{ $resultCountry['name'] }}
                                            </a>
                                        @else
                                            {{ $result['country_code'] }}
                                        @endif
                                    </dd>
                                </div>
                                <div class="border-b border-line py-3">
                                    <dt class="text-xs font-semibold text-ink-muted">{{ __('ui.vies_page.field_status') }}</dt>
                                    <dd class="mt-1">
                                        <span @class(['app-badge', 'bg-success-soft text-success' => $result['valid'], 'bg-danger-soft text-danger' => ! $result['valid']])>
                                            <x-ui.icon :name="$result['valid'] ? 'check' : 'x'" class="size-3.5" />
                                            {{ $result['valid'] ? __('ui.vies_page.status_active') : __('ui.vies_page.status_inactive') }}
                                        </span>
                                    </dd>
                                </div>
                                <div class="border-b border-line py-3">
                                    <dt class="text-xs font-semibold text-ink-muted">{{ __('ui.vies_page.field_source') }}</dt>
                                    <dd class="mt-1 inline-flex items-center gap-1.5 text-sm font-medium {{ $sourceTone }}">
                                        <x-ui.icon :name="$sourceIcon" class="size-4" />
                                        {{ $sourceLabel }}
                                    </dd>
                                </div>
                                @if($result['valid'])
                                    <div class="border-b border-line py-3">
                                        <dt class="text-xs font-semibold text-ink-muted">{{ __('ui.vies_page.field_company') }}</dt>
                                        <dd @class(['mt-1', 'font-semibold text-ink' => $result['name'], 'text-sm text-ink-quiet' => ! $result['name']])>{{ $result['name'] ?? __('ui.vies_page.not_provided') }}</dd>
                                    </div>
                                    <div class="border-b border-line py-3">
                                        <dt class="text-xs font-semibold text-ink-muted">{{ __('ui.vies_page.field_address') }}</dt>
                                        <dd @class(['mt-1 whitespace-pre-line', 'text-ink' => $result['address'], 'text-sm text-ink-quiet' => ! $result['address']])>{{ $result['address'] ?? __('ui.vies_page.not_provided') }}</dd>
                                    </div>
                                @endif
                                @if($result['request_identifier'])
                                    <div class="border-b border-line py-3 sm:col-span-2">
                                        <dt class="text-xs font-semibold text-ink-muted">{{ __('ui.vies_page.field_request_id') }}</dt>
                                        <dd class="mt-1 font-mono text-sm text-ink">{{ $result['request_identifier'] }}</dd>
                                    </div>
                                @endif
                            </dl>

                            @if($result['lookups'] > 1)
                                <p class="px-5 pb-4 pt-1 text-xs text-ink-muted sm:px-7">{{ __('ui.vies_page.lookup_count', ['count' => number_format($result['lookups'])]) }}</p>
                            @else
                                <div class="pb-2"></div>
                            @endif
                        </div>
                    @endif
                </div>
            </div>

            <ul class="mx-auto mt-6 flex max-w-4xl flex-wrap items-center justify-center gap-x-6 gap-y-2 text-sm text-white/80">
                <li class="inline-flex items-center gap-1.5"><x-ui.icon name="shield-check" class="size-4" />{{ __('ui.vies_page.trust_official') }}</li>
                <li class="inline-flex items-center gap-1.5"><x-ui.icon name="zap" class="size-4" />{{ __('ui.vies_page.trust_instant') }}</li>
                <li class="inline-flex items-center gap-1.5"><x-ui.icon name="check" class="size-4" />{{ __('ui.vies_page.trust_free') }}</li>
            </ul>
        </div>
    </section>

    <div class="app-container py-10 sm:py-14">
        <div class="grid grid-cols-1 items-start gap-8 lg:grid-cols-[minmax(0,1fr)_20rem]">
            <div class="min-w-0 space-y-8">
                @if($pageCountry && $format)
                    <section class="app-surface p-5 sm:p-6" aria-labelledby="vat-number-format">
                        <p class="app-eyebrow">{{ __('ui.country_page.eu_guidance') }}</p>
                        <h2 id="vat-number-format" class="mt-1 text-xl font-bold text-ink">{{ __('ui.vies_page.format_heading', ['country' => $pageCountry->name]) }}</h2>
                        <dl class="mt-4 grid gap-4 sm:grid-cols-2">
                            <div class="rounded-card bg-surface-subtle p-4">
                                <dt class="text-xs font-semibold text-ink-muted">{{ __('ui.vies_page.format_prefix') }}</dt>
                                <dd class="mt-1 font-mono text-2xl font-bold text-ink">{{ $format['prefix'] }}</dd>
                            </div>
                            <div class="rounded-card bg-surface-subtle p-4">
                                <dt class="text-xs font-semibold text-ink-muted">{{ __('ui.vies_page.format_structure') }}</dt>
                                <dd class="mt-1 font-mono text-lg font-bold break-all text-ink">{{ VatNumberFormat::describe($format) }}</dd>
                            </div>
                        </dl>
                        <p class="mt-3 text-xs leading-5 text-ink-muted">{{ __('ui.vies_page.format_legend') }}</p>
                        <p class="mt-4 text-sm leading-6 text-ink-muted">{{ $format['prefix'] === 'EL' ? __('ui.vies_page.format_note_gr') : __('ui.vies_page.format_note_default') }}</p>
                        <p class="mt-2 text-sm leading-6 text-ink-muted">{{ __('ui.vies_page.format_disclaimer') }}</p>
                        <a class="app-link mt-4 inline-flex items-center gap-1 text-sm font-semibold" href="{{ $format['source_url'] }}" target="_blank" rel="noopener noreferrer">
                            {{ __('ui.vies_page.format_source') }}
                            <x-ui.icon name="arrow-up-right" class="size-3.5" />
                        <x-ui.new-tab /></a>
                    </section>
                @endif

                <section class="app-surface p-5 sm:p-6" aria-labelledby="about-vies">
                    <h2 id="about-vies" class="text-xl font-bold text-ink">
                        {{ $inVies ? __('ui.vies_page.about_vat_country', ['country' => $pageCountry->name]) : __('ui.vies_page.what_is_vies') }}
                    </h2>
                    <div class="app-prose mt-3 max-w-[72ch] text-sm leading-6 text-ink-muted">
                        @if($inVies)
                            <p>{{ __('ui.vies_page.country_vies_p1', ['country' => $pageCountry->name, 'code' => $format['prefix'] ?? $pageCountry->iso_code]) }}</p>
                            <p>{!! __('ui.vies_page.country_vies_p2', [
                                'country' => e($pageCountry->name),
                                'rate' => e(Country::formatRate($pageCountry->standard_rate)),
                                'calculator_link' => '<a href="'.e(locale_path('/vat-calculator/'.$pageCountry->slug)).'" class="app-link font-semibold">'.e(__('ui.vies_page.country_calculator_link', ['country' => $pageCountry->name])).'</a>',
                            ]) !!}</p>
                        @else
                            <p>{{ __('ui.vies_page.vies_p1') }}</p>
                            <p>{{ __('ui.vies_page.vies_p2') }}</p>
                            <p>{{ __('ui.vies_page.vies_p3') }}</p>
                        @endif
                    </div>
                </section>

                @unless($pageCountry)
                    <section class="app-surface app-deferred overflow-hidden" aria-labelledby="vat-number-formats">
                        <div class="border-b border-line px-5 py-4 sm:px-6">
                            <h2 id="vat-number-formats" class="text-xl font-bold text-ink">{{ __('ui.vies_page.formats_heading') }}</h2>
                            <p class="mt-1 max-w-[72ch] text-sm text-ink-muted">{{ __('ui.vies_page.formats_intro') }}</p>
                        </div>
                        <div class="relative overflow-x-auto" tabindex="0" role="group" aria-labelledby="vat-number-formats">
                            <table class="app-table min-w-[30rem]">
                                <thead>
                                    <tr>
                                        <th scope="col" class="pl-5 sm:pl-6">{{ __('ui.vies_page.formats_th_country') }}</th>
                                        <th scope="col" class="pr-5 sm:pr-6">{{ __('ui.vies_page.format_structure') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($this->countries as $option)
                                        @php($countryFormat = config('vat-number-formats.'.$option['iso']))
                                        @continue(! $countryFormat)
                                        <tr>
                                            <th scope="row" class="pl-5 font-normal sm:pl-6">
                                                <a href="{{ locale_path('/vat-number-validator/'.$option['slug']) }}" class="flex min-h-10 items-center gap-3 font-semibold text-ink hover:text-action">
                                                    <x-ui.flag :iso="$option['iso']" />
                                                    {{ $option['name'] }}
                                                </a>
                                            </th>
                                            <td class="pr-5 font-mono text-sm font-semibold break-all text-ink sm:pr-6">{{ VatNumberFormat::describe($countryFormat) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <p class="border-t border-line px-5 py-3 text-xs leading-5 text-ink-muted sm:px-6">{{ __('ui.vies_page.format_legend') }}</p>
                    </section>
                @endunless

                <x-faq-list :faqs="$faqs" :title="__('ui.vies_page.faq_heading')" id="vies-faq" />

                <section class="app-surface app-deferred overflow-hidden" aria-labelledby="vies-api" x-data="apiPlayground(@js(['endpoint' => $endpoint]))">
                    <div class="flex flex-wrap items-start justify-between gap-3 border-b border-line px-5 py-4 sm:px-6">
                        <div>
                            <h2 id="vies-api" class="flex items-center gap-2 text-lg font-bold text-ink">
                                <x-ui.icon name="code" class="size-5 text-action" />
                                {{ __('ui.vies_page.api_heading') }}
                            </h2>
                            <p class="mt-1 text-sm text-ink-muted">{{ __('ui.vies_page.api_intro') }}</p>
                        </div>
                        <a href="{{ locale_path('/vat-validation-api') }}" class="app-link inline-flex items-center gap-1 text-sm font-semibold">
                            {{ __('ui.vies_page.api_docs_link') }}
                            <x-ui.icon name="arrow-right" class="size-3.5" />
                        </a>
                    </div>

                    <div class="space-y-5 p-5 sm:p-6">
                        <div class="rounded-card border border-line bg-surface-subtle p-4">
                            <p class="text-xs font-semibold text-ink-muted">{{ __('ui.vies_page.api_endpoint') }}</p>
                            <div class="mt-2 flex flex-wrap items-center gap-2">
                                <span class="rounded-control bg-action-soft px-2 py-0.5 font-mono text-xs font-bold text-action-deep">POST</span>
                                <code class="min-w-0 flex-1 break-all font-mono text-sm text-ink">{{ $endpoint }}</code>
                                <button type="button" x-on:click="$copy(@js($endpoint), @js(__('ui.calculator.copied')))" class="app-button-ghost size-9 min-h-9 p-0" aria-label="{{ __('ui.calculator.copy') }}">
                                    <x-ui.icon name="copy" class="size-4" />
                                </button>
                            </div>
                            <p class="mt-2 text-xs text-ink-muted">{{ __('ui.vies_page.api_meta') }}</p>
                        </div>

                        <div>
                            <p class="text-sm font-semibold text-ink">{{ __('ui.vies_page.api_try') }}</p>
                            <div class="mt-3 grid gap-3 sm:grid-cols-[8rem_minmax(0,1fr)]">
                                <div>
                                    <label for="api-country" class="mb-1 block font-mono text-xs text-ink-muted">country_code</label>
                                    <input id="api-country" x-model="country" type="text" maxlength="2" autocomplete="off" spellcheck="false" class="app-field h-10 min-h-10 font-mono text-sm uppercase">
                                </div>
                                <div>
                                    <label for="api-number" class="mb-1 block font-mono text-xs text-ink-muted">vat_number</label>
                                    <input id="api-number" x-model="number" x-on:keydown.enter.prevent="send()" type="text" maxlength="40" autocomplete="off" spellcheck="false" class="app-field h-10 min-h-10 font-mono text-sm">
                                </div>
                            </div>

                            <div class="app-code mt-3">
                                <div class="flex items-center justify-between gap-3 border-b border-white/10 px-4 py-2">
                                    <p class="truncate font-mono text-xs text-syntax-comment"><span class="font-semibold text-syntax-ok">POST</span> /api/vat/validation/validate</p>
                                    <button type="button" x-on:click="send()" :disabled="loading" class="pressable inline-flex h-8 shrink-0 items-center gap-1.5 rounded-control bg-button px-3.5 text-xs font-semibold text-white hover:bg-button-hover disabled:cursor-not-allowed disabled:opacity-60">
                                        <x-ui.icon name="play" class="size-3.5" x-show="!loading" />
                                        <span x-cloak x-show="loading" class="size-3.5 animate-spin rounded-full border-2 border-white/40 border-t-white" aria-hidden="true"></span>
                                        <span x-text="loading ? @js(__('ui.vies_page.api_sending')) : @js(__('ui.vies_page.api_send'))">{{ __('ui.vies_page.api_send') }}</span>
                                    </button>
                                </div>
                                <pre tabindex="0" class="overflow-x-auto p-4 font-mono text-xs leading-5 text-syntax-literal" x-text="body"></pre>
                                <div class="border-t border-white/10">
                                    <p class="sr-only" role="status" x-text="status === null ? '' : (status === 0 ? @js(__('ui.mcp_page.network_error')) : 'HTTP ' + status + (elapsed !== null ? ', ' + elapsed + ' ms' : ''))"></p>
                                    <div x-show="status !== null" x-cloak class="flex items-center justify-between px-4 pt-3 font-mono text-xs">
                                        <span :class="ok ? 'text-syntax-ok' : 'text-syntax-error'" x-text="status === 0 ? @js(__('ui.mcp_page.network_error')) : 'HTTP ' + status"></span>
                                        <span class="text-syntax-comment" x-text="elapsed !== null ? elapsed + ' ms' : ''"></span>
                                    </div>
                                    <pre tabindex="0" x-show="response" x-cloak class="max-h-80 overflow-auto p-4 font-mono text-xs leading-5" :class="ok ? 'text-syntax-string' : 'text-syntax-error'" x-text="response"></pre>
                                    <p x-show="!response && !loading" class="p-4 font-mono text-xs text-syntax-comment">{{ __('ui.vies_page.api_response_placeholder') }}</p>
                                </div>
                            </div>
                        </div>

                        <a href="{{ locale_path('/vat-validation-api') }}" class="group flex items-center justify-between gap-4 rounded-card border border-line p-4 transition-colors hover:border-action/40 hover:bg-action-soft">
                            <span>
                                <span class="block text-sm font-semibold text-ink">{{ __('ui.vies_page.api_docs_link') }}</span>
                                <span class="block text-sm text-ink-muted">{{ __('ui.vies_page.api_docs_desc') }}</span>
                            </span>
                            <x-ui.icon name="arrow-right" class="size-4 text-action transition-transform group-hover:translate-x-0.5" />
                        </a>
                    </div>
                </section>

                <p class="max-w-[72ch] text-xs leading-5 text-ink-muted">{{ __('ui.vies_page.disclaimer') }}</p>
            </div>

            <aside class="space-y-6 lg:sticky lg:top-24">
                <section
                    x-data="lookupHistory"
                    x-on:validation-complete.window="record($event.detail)"
                    x-show="items.length > 0"
                    x-cloak
                    class="app-surface p-5"
                    aria-labelledby="recent-lookups"
                >
                    <div class="flex items-center justify-between gap-2">
                        <h2 id="recent-lookups" class="text-base font-bold text-ink">{{ __('ui.vies_page.recent_heading') }}</h2>
                        <button type="button" x-on:click="clear()" class="min-h-11 rounded-control px-2 py-1 text-xs font-semibold text-ink-muted hover:bg-surface-muted hover:text-ink">{{ __('ui.calculator.clear_all') }}</button>
                    </div>
                    <ul class="mt-3 space-y-1.5">
                        <template x-for="item in items" :key="item.cc + item.vn">
                            <li>
                                <button type="button" x-on:click="open(item)" class="flex w-full items-center gap-3 rounded-control border border-line px-3 py-2 text-left transition-colors hover:border-action/40 hover:bg-action-soft">
                                    <span class="min-w-0 flex-1">
                                        <span class="block truncate font-mono text-sm font-semibold text-ink" x-text="(item.prefix || item.cc) + item.vn"></span>
                                        <span class="block truncate text-xs text-ink-muted" x-show="item.name" x-text="item.name"></span>
                                    </span>
                                    <span class="inline-flex shrink-0 items-center gap-1 text-xs font-semibold" :class="item.valid ? 'text-success' : 'text-danger'">
                                        <span class="size-2 rounded-full" :class="item.valid ? 'bg-success' : 'bg-danger'" aria-hidden="true"></span>
                                        <span x-text="item.valid ? @js(__('ui.vies_page.status_active')) : @js(__('ui.vies_page.status_inactive'))"></span>
                                    </span>
                                </button>
                            </li>
                        </template>
                    </ul>
                    <p class="mt-3 flex items-center gap-1.5 text-xs text-ink-muted">
                        <x-ui.icon name="lock" class="size-3.5" />
                        {{ __('ui.vies_page.recent_privacy') }}
                    </p>
                </section>

                <nav class="app-surface p-5" aria-labelledby="country-validators">
                    <h2 id="country-validators" class="text-base font-bold text-ink">{{ __('ui.vies_page.country_validators') }}</h2>
                    <ul class="-mx-2 mt-3 max-h-[26rem] space-y-0.5 overflow-y-auto overscroll-contain">
                        @foreach($this->countries as $option)
                            @php($current = $pageCountry?->slug === $option['slug'])
                            <li>
                                <a
                                    href="{{ locale_path('/vat-number-validator/'.$option['slug']) }}"
                                    title="{{ __('ui.vies_page.country_h1', ['country' => $option['name']]) }}"
                                    aria-label="{{ __('ui.vies_page.country_h1', ['country' => $option['name']]) }}"
                                    @if($current) aria-current="page" @endif
                                    @class(['flex min-h-10 items-center gap-2.5 rounded-control px-2 text-[0.8125rem] transition-colors', 'bg-action-soft font-semibold text-action-deep' => $current, 'text-ink-muted hover:bg-surface-subtle hover:text-ink' => ! $current])
                                >
                                    <x-ui.flag :iso="$option['iso']" size="xs" />
                                    <span class="min-w-0 flex-1 truncate">{{ $option['name'] }}</span>
                                    <span class="font-mono text-xs text-ink-quiet">{{ $option['prefix'] }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </nav>

                <nav class="app-surface p-5" aria-labelledby="related-tools">
                    <h2 id="related-tools" class="text-base font-bold text-ink">{{ __('ui.vies_page.related_tools') }}</h2>
                    <ul class="mt-3 space-y-1">
                        <li>
                            <a href="{{ locale_path('/vat-calculator') }}" class="-mx-2 flex items-start gap-3 rounded-control p-2 transition-colors hover:bg-surface-subtle">
                                <span class="flex size-8 shrink-0 items-center justify-center rounded-control bg-action-soft text-action"><x-ui.icon name="calculator" class="size-4" /></span>
                                <span class="min-w-0">
                                    <span class="block text-sm font-semibold text-ink">{{ __('ui.nav.vat_calculator') }}</span>
                                    <span class="block text-xs leading-5 text-ink-muted">{{ __('ui.palette.calculator_subtitle') }}</span>
                                </span>
                            </a>
                        </li>
                        @foreach($relatedTools as $tool)
                            <li>
                                <a href="{{ $tool['url'] }}" class="-mx-2 flex items-start gap-3 rounded-control p-2 transition-colors hover:bg-surface-subtle">
                                    <span class="flex size-8 shrink-0 items-center justify-center rounded-control bg-action-soft text-action"><x-ui.icon :name="$tool['icon']" class="size-4" /></span>
                                    <span class="min-w-0">
                                        <span class="block text-sm font-semibold text-ink">{{ $tool['label'] }}</span>
                                        <span class="block text-xs leading-5 text-ink-muted">{{ $tool['description'] }}</span>
                                    </span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </nav>
            </aside>
        </div>
    </div>
</div>
