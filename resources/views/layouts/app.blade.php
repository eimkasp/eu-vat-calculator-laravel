<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="color-scheme" content="light dark">
    <meta name="theme-color" content="#ffffff">
    <script data-cfasync="false">
        (() => {
            let mode = 'system';
            try { mode = localStorage.getItem('theme') || 'system'; } catch (error) {}
            const dark = mode === 'dark' || (mode !== 'light' && window.matchMedia('(prefers-color-scheme: dark)').matches);
            document.documentElement.dataset.theme = dark ? 'dark' : 'light';
            if (dark) document.querySelector('meta[name="theme-color"]').setAttribute("content", "#000000");
        })();
    </script>
    <link rel="manifest" href="/manifest.json">
    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="apple-touch-icon" href="/icon-192x192.png">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles

    @hasSection('seo')
        @yield('seo')
    @else
        <x-seo-meta :title="trim($__env->yieldContent('title')) ?: null" :description="trim($__env->yieldContent('meta_description')) ?: null" />
    @endif

    @php
        $seoPolicy = app(\App\Support\Seo\SeoPolicy::class);
        $supportedLocales = $seoPolicy->indexableLocales();
        $defaultLocale = config('translation.default_language', 'en');
        $cleanPath = preg_replace('#^('.implode('|', $supportedLocales).')(/|$)#', '/', request()->path());
        $cleanPath = $cleanPath === '' ? '/' : $cleanPath;
        $cleanPath = $cleanPath !== '/' && ! str_starts_with($cleanPath, '/') ? '/'.$cleanPath : $cleanPath;
    @endphp
    @if($seoPolicy->shouldEmitHreflang())
        <link rel="alternate" hreflang="x-default" href="{{ $seoPolicy->localizedUrl($cleanPath, $defaultLocale) }}">
        @foreach($supportedLocales as $hrefLocale)
            <link rel="alternate" hreflang="{{ $hrefLocale }}" href="{{ $seoPolicy->localizedUrl($cleanPath, $hrefLocale) }}">
        @endforeach
    @endif

    <link rel="alternate" type="text/markdown" href="{{ $seoPolicy->canonicalizeLocalUrl(url()->current()) }}" title="Markdown">

    @stack('head')

    @if (config('app.data_domain') && app()->isProduction())
        <script defer data-cfasync="false" data-domain="{{ config('app.plausible_domain') }}" src="{{ config('app.plausible_script') ?: 'https://stats.businesspress.io/js/script.js' }}"></script>
    @endif
</head>

<body class="app-workspace min-h-dvh font-sans antialiased">
    @stack('svg-sprites')
    <a href="#main-content" class="sr-only z-[60] rounded-br-card bg-button px-4 py-3 text-sm font-semibold text-white focus:not-sr-only focus:fixed focus:left-0 focus:top-0">{{ __('ui.skip_to_content') }}</a>

    <x-global-header />

    <main id="main-content" tabindex="-1" class="outline-none">
        @isset($slot)
            {{ $slot }}
        @else
            @yield('content')
        @endisset
    </main>

    <x-footer />
    <x-bottom-navigation />
    <x-command-palette />
    <x-toasts />

    @livewireScripts

    <script data-cfasync="false">
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => navigator.serviceWorker.register('/sw.js').catch(() => {}));
        }
    </script>

    {{-- WebMCP: exposes read-only site tools to AI agents running in the browser. --}}
    <script data-cfasync="false">
        (() => {
            if (!navigator.modelContext) return;

            const baseUrl = @json(rtrim(config('app.url'), '/'));
            const getJson = async (path, init) => (await fetch(baseUrl + path, { headers: { Accept: 'application/json' }, ...init })).json();

            navigator.modelContext.registerTool({
                name: 'get_all_vat_rates',
                description: 'Get current VAT rates (standard, reduced, super-reduced, parking) for all 27 EU member states.',
                inputSchema: { type: 'object', properties: {}, additionalProperties: false },
                execute: () => getJson('/api/countries'),
            });

            navigator.modelContext.registerTool({
                name: 'get_country_vat_rate',
                description: 'Get VAT rates for a specific EU country by slug (e.g. germany).',
                inputSchema: {
                    type: 'object',
                    properties: { country: { type: 'string', description: 'Country slug, e.g. germany' } },
                    required: ['country'],
                    additionalProperties: false,
                },
                execute: ({ country }) => getJson('/api/countries/' + encodeURIComponent(country)),
            });

            navigator.modelContext.registerTool({
                name: 'calculate_vat',
                description: 'Calculate VAT for an amount in an EU country. Returns net, VAT and gross amounts.',
                inputSchema: {
                    type: 'object',
                    properties: {
                        amount: { type: 'number', description: 'Monetary amount' },
                        country: { type: 'string', description: 'Country slug or ISO code' },
                        mode: { type: 'string', enum: ['add', 'remove'], description: 'add = net to gross, remove = gross to net. Default: add' },
                        rate_type: { type: 'string', enum: ['standard', 'reduced', 'super_reduced', 'parking'], description: 'VAT rate type. Default: standard' },
                    },
                    required: ['amount', 'country'],
                    additionalProperties: false,
                },
                execute: ({ amount, country, mode, rate_type }) => {
                    const params = new URLSearchParams({ amount, country, mode: mode === 'remove' ? 'remove' : 'add', rate_type: rate_type || 'standard' });
                    return getJson('/api/v1/calculate?' + params);
                },
            });

            navigator.modelContext.registerTool({
                name: 'validate_vat_number',
                description: 'Validate an EU VAT number against the official VIES database. Returns validity, company name, and address.',
                inputSchema: {
                    type: 'object',
                    properties: {
                        country_code: { type: 'string', description: 'Two-letter country code (use EL for Greece)' },
                        vat_number: { type: 'string', description: 'VAT number without country prefix' },
                    },
                    required: ['country_code', 'vat_number'],
                    additionalProperties: false,
                },
                execute: ({ country_code, vat_number }) => getJson('/api/vat/validation/validate', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
                    body: JSON.stringify({ country_code, vat_number }),
                }),
            });

            navigator.modelContext.registerTool({
                name: 'search_countries',
                description: 'Open the VAT calculator for a country, or the EU VAT Info homepage when no query is given.',
                inputSchema: {
                    type: 'object',
                    properties: { query: { type: 'string', description: 'Country slug to open (optional)' } },
                    additionalProperties: false,
                },
                execute: ({ query }) => {
                    window.location.href = baseUrl + (query ? '/vat-calculator/' + encodeURIComponent(query) : '/');
                    return { navigated: true };
                },
            });
        })();
    </script>
</body>
</html>
