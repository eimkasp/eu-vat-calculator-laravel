@section('seo')
    @php
        $base = rtrim((string) config('seo.canonical_url'), '/');
    @endphp
    <x-seo-meta :title="$landingPage ? __('landing.meta_title') : __('ui.home_page.title')" :description="$landingPage ? __('landing.meta_description') : __('ui.home_page.meta_desc')" type="website">
        <x-json-ld :data="[
            '@type' => 'WebSite',
            '@id' => $base.'/#website',
            'name' => 'EU VAT Info',
            'alternateName' => array_values(array_unique(['vat.businesspress.io', __('ui.site_name')])),
            'url' => $base.'/',
            'description' => __('ui.home_page.meta_desc'),
            'inLanguage' => app()->getLocale(),
            'publisher' => ['@id' => $base.'/#organization'],
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => ['@type' => 'EntryPoint', 'urlTemplate' => url(locale_path('/')).'?search={search_term_string}'],
                'query-input' => 'required name=search_term_string',
            ],
        ]" />
        <x-json-ld :data="[
            '@type' => 'Organization',
            '@id' => $base.'/#organization',
            'name' => 'EU VAT Info',
            'url' => $base.'/',
            'logo' => $base.'/icon-512x512.png',
            'description' => 'Current EU VAT rates, VAT calculators, VIES VAT number validation and open VAT data for the 27 EU member states.',
            'sameAs' => [
                'https://github.com/eimkasp/eu-vat.info',
                'https://chromewebstore.google.com/detail/eu-vat-calculator/fifmbbpgopnifnoginhmjjedjnabdkka',
            ],
        ]" />
        <x-json-ld :data="\App\Support\Seo\DatasetSchema::rates()" />
        @if($landingPage)
            <x-json-ld :data="[
                '@type' => 'FAQPage',
                'mainEntity' => collect($landing['faqs'])->map(fn (array $faq) => [
                    '@type' => 'Question',
                    'name' => $faq['q'],
                    'acceptedAnswer' => ['@type' => 'Answer', 'text' => $faq['a']],
                ])->all(),
            ]" />
        @endif
    </x-seo-meta>
@endsection

@if(app()->getLocale() === config('translation.default_language', 'en'))
    @push('head')
        <link rel="amphtml" href="{{ url('/amp') }}">
    @endpush
@endif

@unless($landingPage)
<div>
    <section class="hero-canvas" aria-labelledby="home-heading">
        <x-hero-backdrop />
        <div class="app-container relative pb-12 pt-10 sm:pb-16 sm:pt-14">
            <div class="mx-auto mb-8 max-w-3xl text-center sm:mb-10">
                <p class="app-kicker">{{ __('ui.home_page.hero_badge', ['year' => date('Y'), 'count' => $stats['count'] ?? 27]) }}</p>
                <h1 id="home-heading" class="mt-4 text-4xl font-bold tracking-[-0.03em] text-white sm:text-5xl sm:leading-[1.08]">
                    {{ __('ui.home_page.heading') }}
                    {{ __('ui.home_page.heading_accent') }}
                </h1>
                <p class="mx-auto mt-4 max-w-[62ch] text-base leading-7 text-white/80 sm:text-lg">
                    {{ __('ui.home_page.subtitle') }}
                </p>
            </div>

            <livewire:hero-calculator surface="dark" />
        </div>
    </section>

    <section class="app-container py-12 sm:py-16">
        <div class="grid grid-cols-1 items-start gap-8 lg:grid-cols-[minmax(0,1fr)_22rem]">
            <x-country-rates-table :countries="$countries" :search="$search" :stats="$stats" />
            <x-home-sidebar />
        </div>
    </section>
</div>
@else
<div x-data="reveal">
    @php
        $worked = $landing['worked'];
        $latest = $landing['latest'];
        $benefitIcons = ['preset' => 'ph-percent', 'cent' => 'ph-plus-minus', 'source' => 'ph-calendar-check', 'share' => 'ph-share-network', 'amounts' => 'ph-keyboard'];
        $proofIcons = ['commission' => 'ph-bank', 'dataset' => 'ph-database', 'vies' => 'ph-seal-check', 'maths' => 'ph-equals', 'code' => 'ph-github-logo', 'languages' => 'ph-translate'];
        $proofLinks = [
            'commission' => config('calculator.rates_source_url'),
            'dataset' => config('calculator.dataset_source_url'),
            'vies' => locale_path('/vat-number-validator'),
            'code' => 'https://github.com/eimkasp/eu-vat-calculator-laravel',
        ];
        $summaryExample = $worked ? strtr(__('ui.calculator.summary_add'), [':amount' => $worked['net'], ':rate' => $worked['rate'], ':country' => $worked['country']]) : '';
    @endphp
    {{-- 1. Hero: the calculator is the page's one primary action and its visual. --}}
    <section class="bg-workspace" aria-labelledby="home-heading">
        <div class="app-container pb-16 pt-8 sm:pb-20 sm:pt-16">
            <h1 id="home-heading" class="lp-heading max-w-[680px] text-3xl font-semibold tracking-tight min-[360px]:text-4xl sm:text-6xl">
                {{ __('landing.hero.line_one') }}<br>
                {{ __('landing.hero.line_two') }}
            </h1>
            <p class="mt-6 max-w-[680px] text-lg text-pretty text-ink-muted">{{ __('landing.hero.subheadline') }}</p>

            <div id="calculator" role="region" aria-label="{{ __('landing.hero.calculator_label') }}" class="mt-10 scroll-mt-24">
                <livewire:hero-calculator surface="home" />
            </div>
        </div>
    </section>

    {{-- 2. Problem to solution, with the live record beside the claim. --}}
    <section class="border-y border-line bg-surface-subtle" aria-labelledby="problem-heading">
        <div data-reveal-item class="app-container grid grid-cols-1 items-start gap-12 py-20 lg:grid-cols-2 lg:py-24">
            <div>
                <h2 id="problem-heading" class="max-w-[680px] text-3xl font-semibold tracking-tight text-balance text-ink sm:text-4xl">{{ __('landing.problem.heading') }}</h2>
                <p class="mt-6 max-w-[64ch] text-lg text-pretty text-ink-muted">
                    @if($latest)
                        {{ __('landing.problem.body', ['count' => number_format($landing['change_count']), ...$latest]) }}
                    @else
                        {{ __('landing.problem.body_empty') }}
                    @endif
                </p>
                <a href="{{ locale_path('/vat-changes') }}" class="app-link mt-6 inline-flex min-h-12 items-center gap-2 text-balance text-base">
                    {{ $landing['change_count'] ? __('landing.problem.link', ['count' => number_format($landing['change_count'])]) : __('landing.problem.link_empty') }}
                    <x-ui.icon name="ph-arrow-right" class="size-4" />
                </a>
            </div>
            <livewire:vat-rate-changes />
        </div>
    </section>

    {{-- 3. Today's rates for the 27 member states, dated and sourced: the answer to section 2. --}}
    <section class="app-container py-20 lg:py-24">
        <div data-reveal-item>
            <x-country-rates-table :countries="$countries" :search="$search" :stats="$stats" />
            <a href="{{ locale_path('/vat-map') }}" class="app-link mt-6 inline-flex min-h-12 items-center gap-2 text-balance text-base">
                {{ __('landing.rates.map_link') }}
                <x-ui.icon name="ph-arrow-right" class="size-4" />
            </a>
        </div>
    </section>

    {{-- 4. Benefits, each with its proof beside it. --}}
    <section class="app-container pb-20 lg:pb-24" aria-labelledby="benefits-heading">
        <h2 id="benefits-heading" data-reveal-item class="max-w-[680px] text-3xl font-semibold tracking-tight text-balance text-ink sm:text-4xl">{{ __('landing.benefits.heading') }}</h2>
        <ul class="mt-12 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @foreach(__('landing.benefits.items') as $key => $benefit)
                @continue($key === 'cent' && ! $worked)
                @continue($key === 'preset' && ! $landing['reduced'])
                <li data-reveal-item class="rounded-card border border-line bg-surface p-6">
                    <span class="flex size-12 items-center justify-center rounded-control bg-surface-muted text-ink">
                        <x-ui.icon :name="$benefitIcons[$key]" class="size-6" />
                    </span>
                    <h3 class="mt-6 text-lg font-semibold text-ink">{{ $benefit['title'] }}</h3>
                    <p class="mt-2 text-base text-pretty text-ink-muted">
                        @switch($key)
                            @case('preset'){{ __('landing.benefits.items.preset.detail', $landing['reduced']) }}@break
                            @case('cent'){{ __('landing.benefits.items.cent.detail', $worked) }}@break
                            @case('share'){{ __('landing.benefits.items.share.detail', ['summary' => $summaryExample]) }}@break
                            @default{{ $benefit['detail'] }}
                        @endswitch
                    </p>
                    @if($key === 'preset' && $landing['france'])
                        <a href="{{ locale_path('/vat-calculator/'.$landing['france']->slug) }}" class="app-link mt-4 inline-flex min-h-12 items-center gap-2 text-balance text-sm">
                            {{ __('landing.benefits.items.preset.link', ['country' => $landing['france']->name]) }}
                            <x-ui.icon name="ph-arrow-right" class="size-4" />
                        </a>
                    @endif
                </li>
            @endforeach
        </ul>
    </section>

    {{-- 5. Tagline reveal (skill B11): each word moves from the muted tone to full ink as it crosses the trigger line. --}}
    <section class="border-y border-line bg-surface-subtle">
        <div class="app-container py-24">
            <p x-data="taglineReveal" class="max-w-[680px] text-4xl font-semibold tracking-tight">
                <span class="sr-only">{{ implode(' ', __('landing.tagline')) }}</span>
                @foreach(__('landing.tagline') as $line)
                    <span class="block text-balance" aria-hidden="true">
                        @foreach(explode(' ', $line) as $word)<span class="lp-word" data-word>{{ $word }}</span>{{ $loop->last ? '' : ' ' }}@endforeach
                    </span>
                @endforeach
            </p>
        </div>
    </section>

    {{-- 6. How it works. --}}
    <section class="app-container py-20 lg:py-24" aria-labelledby="steps-heading">
        <h2 id="steps-heading" data-reveal-item class="max-w-[680px] text-3xl font-semibold tracking-tight text-balance text-ink sm:text-4xl">{{ __('landing.steps.heading') }}</h2>
        <ol class="mt-12 grid grid-cols-1 gap-6 md:grid-cols-3">
            @foreach(__('landing.steps.items') as $step)
                <li data-reveal-item class="rounded-card border border-line bg-surface p-6">
                    <span class="flex size-8 items-center justify-center rounded-full bg-ink text-sm font-semibold text-workspace">{{ $loop->iteration }}</span>
                    <h3 class="mt-6 text-lg font-semibold text-ink">{{ $step['title'] }}</h3>
                    <p class="mt-2 text-base text-pretty text-ink-muted">{{ strtr($step['detail'], [':count' => $landing['calculator_count']]) }}</p>
                </li>
            @endforeach
        </ol>
        <a href="#calculator" data-reveal-item class="app-link mt-6 inline-flex min-h-12 items-center gap-2 text-balance text-base">
            {{ __('landing.steps.start') }}
            <x-ui.icon name="ph-arrow-right" class="size-4" />
        </a>
    </section>

    {{-- 7. Proof: sources anyone can check, in place of testimonials. --}}
    <section class="border-y border-line bg-surface-subtle" aria-labelledby="proof-heading">
        <div class="app-container py-20 lg:py-24">
            <div data-reveal-item>
                <h2 id="proof-heading" class="max-w-[680px] text-3xl font-semibold tracking-tight text-balance text-ink sm:text-4xl">{{ __('landing.proof.heading') }}</h2>
                <p class="mt-4 max-w-[680px] text-lg text-pretty text-ink-muted">{{ __('landing.proof.intro') }}</p>
            </div>
            <dl class="mt-12 grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-3">
                @foreach(__('landing.proof.items') as $key => $item)
                    <div data-reveal-item>
                        <dt class="flex items-start gap-4 text-base font-semibold text-ink">
                            <x-ui.icon :name="$proofIcons[$key]" class="mt-0.5 size-6 shrink-0" />
                            @isset($proofLinks[$key])
                                <a href="{{ $proofLinks[$key] }}" @if(str_starts_with($proofLinks[$key], 'http')) target="_blank" rel="noopener noreferrer" @endif class="underline decoration-line-strong underline-offset-4 transition-colors hover:decoration-action">{{ $item['title'] }}@if(str_starts_with($proofLinks[$key], 'http'))<x-ui.new-tab />@endif</a>
                            @else
                                <span>{{ $item['title'] }}</span>
                            @endisset
                        </dt>
                        <dd class="mt-1 pl-10 text-base text-pretty text-ink-muted">{{ $item['detail'] }}</dd>
                    </div>
                @endforeach
            </dl>
        </div>
    </section>

    {{-- 8. Secondary intents: text links only, so the calculator stays the one primary action. --}}
    <section class="app-container py-20 lg:py-24" aria-labelledby="more-heading">
        <h2 id="more-heading" data-reveal-item class="max-w-[680px] text-3xl font-semibold tracking-tight text-balance text-ink sm:text-4xl">{{ __('landing.more.heading') }}</h2>
        <div class="mt-12 grid grid-cols-1 gap-6 lg:grid-cols-3">
            <article data-reveal-item class="flex flex-col rounded-card border border-line bg-surface p-6">
                <x-ui.icon name="ph-identification-card" class="size-6 text-ink" />
                <h3 class="mt-6 text-lg font-semibold text-ink">{{ __('landing.more.validator.title') }}</h3>
                <p class="mt-2 flex-1 text-base text-pretty text-ink-muted">{{ __('landing.more.validator.detail') }}</p>
                <a href="{{ locale_path('/vat-number-validator') }}" class="app-link mt-6 inline-flex min-h-12 items-center gap-2 text-balance text-base">{{ __('landing.more.validator.link') }}<x-ui.icon name="ph-arrow-right" class="size-4" /></a>
            </article>
            <article data-reveal-item class="flex flex-col rounded-card border border-line bg-surface p-6">
                <x-ui.icon name="ph-arrows-left-right" class="size-6 text-ink" />
                <h3 class="mt-6 text-lg font-semibold text-ink">{{ __('landing.more.compare.title') }}</h3>
                <p class="mt-2 text-base text-pretty text-ink-muted">{{ __('landing.more.compare.detail') }}</p>
                <ul class="mt-4 flex-1 divide-y divide-line">
                    @foreach($landing['comparisons'] as $comparison)
                        <li>
                            <a href="{{ $comparison['url'] }}" class="group flex min-h-12 items-center gap-3 py-2">
                                <span class="flex shrink-0 -space-x-1" aria-hidden="true">
                                    <x-ui.flag :iso="$comparison['left']->iso_code" size="xs" />
                                    <x-ui.flag :iso="$comparison['right']->iso_code" size="xs" />
                                </span>
                                <span class="min-w-0 flex-1 text-sm font-medium text-ink underline decoration-transparent underline-offset-4 transition-colors group-hover:decoration-action">{{ $comparison['label'] }}</span>
                                <span class="tabular shrink-0 font-mono text-xs text-ink-muted">{{ \App\Models\Country::formatRate($comparison['left']->standard_rate) }}% / {{ \App\Models\Country::formatRate($comparison['right']->standard_rate) }}%</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
                <a href="{{ locale_path('/compare') }}" class="app-link mt-6 inline-flex min-h-12 items-center gap-2 text-balance text-base">{{ __('landing.more.compare.link') }}<x-ui.icon name="ph-arrow-right" class="size-4" /></a>
            </article>
            <article data-reveal-item class="flex flex-col rounded-card border border-line bg-surface p-6">
                <x-ui.icon name="ph-code" class="size-6 text-ink" />
                <h3 class="mt-6 text-lg font-semibold text-ink">{{ __('landing.more.developers.title') }}</h3>
                <p class="mt-2 text-base text-pretty text-ink-muted">{{ __('landing.more.developers.detail') }}</p>
                <pre tabindex="0" class="app-code mt-4 flex-1 overflow-x-auto rounded-control p-3 text-xs"><code><span class="text-syntax-ok">GET</span> <span class="text-syntax-string">/api/v1/calculate?amount=249.90&amp;country=DE&amp;mode=add</span></code></pre>
                <a href="{{ locale_path('/vat-validation-api') }}" class="app-link mt-6 inline-flex min-h-12 items-center gap-2 text-balance text-base">{{ __('landing.more.developers.link') }}<x-ui.icon name="ph-arrow-right" class="size-4" /></a>
            </article>
        </div>
    </section>

    {{-- 9. Questions, built from the same list as the FAQPage structured data. --}}
    <section class="app-container pb-20 lg:pb-24" aria-labelledby="home-faq">
        <div data-reveal-item class="rounded-card border border-line bg-surface">
            <h2 id="home-faq" class="border-b border-line p-6 text-2xl font-semibold tracking-tight text-ink">{{ __('landing.faq.heading') }}</h2>
            <div class="divide-y divide-line">
                @foreach($landing['faqs'] as $faq)
                    <details class="group px-6" @if($loop->first) open @endif>
                        <summary class="lp-fluid flex min-h-16 cursor-pointer list-none items-center justify-between gap-4 py-4 text-base font-semibold text-ink hover:text-action active:translate-y-px [&::-webkit-details-marker]:hidden">
                            <span class="text-balance">{{ $faq['q'] }}</span>
                            <x-ui.icon name="chevron-down" class="lp-fluid size-4 shrink-0 text-ink-quiet group-open:rotate-180 group-hover:text-ink" />
                        </summary>
                        <p class="max-w-[72ch] pb-6 text-base text-pretty text-ink-muted">{{ $faq['a'] }}</p>
                    </details>
                @endforeach
            </div>
        </div>
    </section>

    {{-- 10. Risk reversal, then 11. the final call to action: the same promise as the hero. --}}
    <section class="app-container pb-20 lg:pb-24">
        <p data-reveal-item class="rounded-card border border-line bg-surface-subtle p-6 text-lg text-pretty text-ink-muted">
            <x-ui.icon name="ph-check-circle" class="mr-2 inline size-6 align-[-0.3em] text-success" />{{ __('landing.risk') }}
        </p>
    </section>

    <section class="hero-canvas" aria-label="{{ __('landing.final.cta') }}">
        <div data-reveal-item class="app-container py-20 lg:py-24">
            <h2 class="lp-heading lp-heading-dark max-w-[680px] text-3xl font-semibold tracking-tight min-[360px]:text-4xl sm:text-5xl">
                {{ __('landing.hero.line_one') }}<br>
                {{ __('landing.hero.line_two') }}
            </h2>
            <p class="mt-6 max-w-[680px] text-lg text-pretty text-white/75">{{ __('landing.final.line', ['count' => $landing['calculator_count']]) }}</p>
            <a href="#calculator" x-on:click="$nextTick(() => setTimeout(() => document.querySelector('#calculator input[inputmode=decimal]')?.focus({ preventScroll: true }), 600))" class="app-button-inverse lp-cta mt-10">
                {{ __('landing.final.cta') }}
                <x-ui.icon name="ph-arrow-right" class="size-4" />
            </a>
        </div>
    </section>
</div>
@endunless
