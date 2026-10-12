@section('seo')
    <x-seo-meta
        title="Donate: support free EU VAT rates and tools | EU VAT Info"
        description="Support EU VAT Info, the free and open-source home of EU VAT rates, calculators and validators, through GitHub Sponsors or x402 payments."
        :url="url()->current()" />
@endsection

@php
    $supports = [
        ['database', 'Daily data updates', 'Automated daily syncs from the European Commission and national tax authorities keep all 27 countries up to date.'],
        ['server', 'Servers and infrastructure', 'Hosting, SSL certificates, CDN and database costs that keep responses fast for people and agents worldwide.'],
        ['code', 'API and MCP server', 'The free JSON API and MCP server that let AI assistants such as Claude, Copilot and Cursor use live EU VAT data.'],
        ['languages', 'Translations in 24 languages', 'Translations for all 24 official EU languages, so the tools are usable across the Union.'],
        ['shield-check', 'VIES validation', 'Free VAT number checks against the official EU VIES database, with caching and fuzzy matching.'],
        ['github', 'Open-source development', 'New features, fixes and community contributions on GitHub.'],
    ];
    $protocol = [['$0', 'Protocol fees'], ['0', 'Accounts required'], ['USDC', 'Stablecoin payments'], ['Base', 'Network (L2)']];
@endphp

<div>
    <section class="hero-canvas">
        <x-hero-backdrop />
        <div class="app-container relative pb-12 pt-6 sm:pb-16 sm:pt-8">
            <x-site-breadcrumbs :items="['Donate' => '']" variant="dark" />
            <div class="mt-6 max-w-3xl">
                <p class="app-kicker">Keep it free</p>
                <h1 class="mt-3 text-4xl font-bold tracking-[-0.03em] text-white sm:text-5xl sm:leading-[1.08]">Support EU VAT Info</h1>
                <p class="mt-4 max-w-2xl text-base leading-7 text-white/85 sm:text-lg">Free, open-source EU VAT data for developers, businesses and AI agents. Keep it running with a donation.</p>

                <div class="mt-8 grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <a href="https://github.com/sponsors/eimkasp" target="_blank" rel="noopener noreferrer" class="group app-brand-panel pressable flex items-center gap-4 p-4 hover:border-white/30 hover:bg-white/10">
                        <span class="flex size-11 shrink-0 items-center justify-center rounded-control bg-white/10 text-gold" aria-hidden="true">
                            <x-ui.icon name="heart" class="size-5" />
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="block font-bold text-white">GitHub Sponsors</span>
                            <span class="block text-sm text-white/70">One-time or monthly · For people</span>
                        </span>
                        <x-ui.icon name="arrow-up-right" class="size-5 text-white/55 transition-colors group-hover:text-white" />
                    <x-ui.new-tab /></a>
                    <a href="#x402-test" class="group app-brand-panel pressable flex items-center gap-4 p-4 hover:border-white/30 hover:bg-white/10">
                        <span class="flex size-11 shrink-0 items-center justify-center rounded-control bg-white/10 text-gold" aria-hidden="true">
                            <x-ui.icon name="zap" class="size-5" />
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="block font-bold text-white">x402 micropayment</span>
                            <span class="block text-sm text-white/70">$0.10 USDC on Base · For AI agents</span>
                        </span>
                        <x-ui.icon name="chevron-down" class="size-5 text-white/55 transition-colors group-hover:text-white" />
                    </a>
                </div>
            </div>
        </div>
    </section>

    <div class="app-container space-y-10 py-8 sm:py-10">
        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
            <section class="app-surface p-5 sm:p-6" aria-labelledby="donate-agents">
                <p class="flex items-center gap-2">
                    <span class="app-badge bg-action-soft text-action-deep">For AI agents</span>
                    <span class="text-xs font-semibold text-ink-quiet">x402 protocol</span>
                </p>
                <h2 id="donate-agents" class="mt-3 text-lg font-bold text-ink">Pay per request with x402</h2>

                <div class="mt-4 rounded-card border border-line bg-surface-subtle p-4">
                    <p class="text-[0.8125rem] font-semibold text-ink-muted">Donate endpoint</p>
                    <code class="mt-1.5 block break-all font-mono text-sm text-ink">GET {{ url('/api/x402/donate') }}</code>
                    <p class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-ink-muted">
                        <span class="inline-flex items-center gap-1.5"><span class="size-1.5 rounded-full bg-success" aria-hidden="true"></span>$0.10 USDC</span>
                        <span>Base network</span>
                        <span>x402 v2</span>
                    </p>
                </div>

                <h3 class="mt-5 text-sm font-semibold text-ink">How it works</h3>
                <ol class="mt-3 space-y-2.5 text-sm text-ink-muted">
                    @foreach([
                        'The agent sends a <code class="font-semibold text-ink">GET</code> request to the donate endpoint.',
                        'The server answers <code class="font-semibold text-ink">HTTP 402</code> with the payment requirements.',
                        'The agent signs a USDC payment and retries with <code class="font-semibold text-ink">PAYMENT-SIGNATURE</code>.',
                        'The payment is verified and settled, and a thank-you is returned.',
                    ] as $step)
                        <li class="flex items-start gap-3">
                            <span class="tabular flex size-5 shrink-0 items-center justify-center rounded-full bg-action-soft text-[0.6875rem] font-bold text-action-deep" aria-hidden="true">{{ $loop->iteration }}</span>
                            <span>{!! $step !!}</span>
                        </li>
                    @endforeach
                </ol>
            </section>

            <section class="app-surface flex flex-col p-5 sm:p-6" aria-labelledby="donate-people">
                <p class="flex items-center gap-2">
                    <span class="app-badge bg-action-soft text-action-deep">For people</span>
                    <span class="text-xs font-semibold text-ink-quiet">GitHub Sponsors</span>
                </p>
                <h2 id="donate-people" class="mt-3 text-lg font-bold text-ink">Sponsor on GitHub</h2>
                <p class="mt-2 text-sm leading-6 text-ink-muted">Support development through GitHub Sponsors with a one-time or recurring donation. GitHub matches contributions during sponsorship events.</p>
                <ul class="mt-4 space-y-2 text-sm text-ink-muted">
                    @foreach(['One-time donations from $1', 'Monthly recurring sponsorship', 'GitHub Sponsors matching, when available'] as $point)
                        <li class="flex items-start gap-2">
                            <x-ui.icon name="check" class="mt-0.5 size-4 text-success" />
                            {{ $point }}
                        </li>
                    @endforeach
                </ul>
                <div class="mt-auto pt-6">
                    <a href="https://github.com/sponsors/eimkasp" target="_blank" rel="noopener noreferrer" class="app-button-primary">
                        <x-ui.icon name="heart" class="size-4" />
                        Sponsor on GitHub
                    <x-ui.new-tab /></a>
                </div>
            </section>
        </div>

        <section id="x402-test" class="scroll-mt-24" x-data="x402Test()" aria-labelledby="x402-test-heading">
            <h2 id="x402-test-heading" class="text-xl font-bold text-ink">Test the x402 connection</h2>
            <div class="app-surface mt-4 p-5 sm:p-6">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
                    <p class="flex-1 text-sm leading-6 text-ink-muted">Check that the x402 payment endpoint responds. This sends a request <strong class="text-ink">without</strong> a payment signature, so you should see an HTTP <code class="font-semibold text-ink">402</code> response with the payment requirements.</p>
                    <button type="button" @click="testEndpoint()" :disabled="loading" :aria-busy="loading.toString()" class="app-button-primary shrink-0 disabled:cursor-wait">
                        <x-ui.icon name="zap" class="size-4" x-show="!loading" />
                        <span x-cloak x-show="loading" class="block size-4 animate-spin rounded-full border-2 border-white/30 border-t-white" aria-hidden="true"></span>
                        <span x-text="loading ? 'Testing…' : 'Test connection'">Test connection</span>
                    </button>
                </div>

                <div class="mt-5 overflow-hidden rounded-card bg-code">
                    <p class="border-b border-white/10 px-4 py-2 font-mono text-xs text-syntax-comment">Or try it with curl</p>
                    <pre tabindex="0" class="overflow-x-auto px-4 py-3 font-mono text-sm text-syntax-string">curl -i {{ url('/api/x402/donate') }}</pre>
                </div>

                <template x-if="result !== null">
                    <div class="mt-5 overflow-hidden rounded-card border" :class="result.success ? 'border-success/30' : 'border-danger/30'" role="status">
                        <div class="flex items-center gap-3 px-4 py-3" :class="result.success ? 'bg-success-soft' : 'bg-danger-soft'">
                            <x-ui.icon name="check-circle" class="size-5 text-success" x-show="result.success" />
                            <x-ui.icon name="alert-circle" class="size-5 text-danger" x-show="!result.success" />
                            <p>
                                <span class="text-sm font-bold" :class="result.success ? 'text-success' : 'text-danger'" x-text="result.title"></span>
                                <span class="ml-2 rounded-xs px-2 py-0.5 font-mono text-xs" :class="result.success ? 'bg-success-soft text-success' : 'bg-danger-soft text-danger'" x-text="'HTTP ' + result.status"></span>
                            </p>
                        </div>
                        <pre tabindex="0" class="max-h-64 overflow-auto bg-surface-subtle p-4 font-mono text-xs whitespace-pre-wrap break-all text-ink-muted" x-text="result.body"></pre>
                        <p class="border-t border-line px-4 py-3 text-xs text-ink-muted" x-text="result.explanation"></p>
                    </div>
                </template>

                <template x-if="error !== null">
                    <div class="mt-5 rounded-card border border-danger/30 bg-danger-soft p-4" role="alert">
                        <p class="flex items-center gap-2 text-sm font-bold text-danger">
                            <x-ui.icon name="alert-circle" class="size-4" />
                            Connection failed
                        </p>
                        <p class="mt-1 text-xs text-danger" x-text="error"></p>
                    </div>
                </template>
            </div>
        </section>

        <section aria-labelledby="x402-premium">
            <h2 id="x402-premium" class="text-xl font-bold text-ink">Premium API endpoints (x402)</h2>
            <p class="mt-2 max-w-[68ch] text-sm leading-6 text-ink-muted">These endpoints return enriched data through <a href="https://x402.org" target="_blank" rel="noopener noreferrer" class="app-link">x402 micropayments<x-ui.new-tab /></a>. All core data stays free through the <a href="/api/countries" class="app-link">public API</a>.</p>
            <ul class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($x402Routes as $route => $config)
                    <li class="app-surface flex flex-col p-5">
                        <p class="flex items-center justify-between gap-3">
                            <span class="tabular rounded-xs bg-surface-muted px-2 py-0.5 font-mono text-xs font-semibold text-ink">{{ $config['price'] }}</span>
                            <span class="text-xs font-semibold text-ink-muted">USDC</span>
                        </p>
                        <h3 class="mt-3 text-sm font-bold leading-6 text-ink">{{ $config['description'] }}</h3>
                        <code class="mt-auto block break-all rounded-control border border-line bg-surface-subtle p-2 font-mono text-xs text-ink-muted">{{ $route }}</code>
                    </li>
                @endforeach
            </ul>

            <div class="app-surface mt-4 flex flex-col gap-3 p-5 sm:flex-row sm:items-center sm:justify-between sm:p-6">
                <div>
                    <h3 class="text-sm font-bold text-ink">Machine-readable discovery</h3>
                    <p class="mt-1 text-sm text-ink-muted">AI agents can discover every paid endpoint and its price programmatically.</p>
                </div>
                <code class="block break-all rounded-control bg-code px-3 py-2 font-mono text-sm text-syntax-string">GET {{ url('/api/x402/info') }}</code>
            </div>
        </section>

        <section aria-labelledby="donation-supports">
            <h2 id="donation-supports" class="text-xl font-bold text-ink">What your donation supports</h2>
            <ul class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($supports as [$icon, $title, $text])
                    <li class="app-surface p-5">
                        <div class="flex items-center gap-3">
                            <span class="flex size-10 shrink-0 items-center justify-center rounded-control bg-action-soft text-action" aria-hidden="true">
                                <x-ui.icon :name="$icon" class="size-5" />
                            </span>
                            <h3 class="text-base font-bold text-ink">{{ $title }}</h3>
                        </div>
                        <p class="mt-3 text-sm leading-6 text-ink-muted">{{ $text }}</p>
                    </li>
                @endforeach
            </ul>
        </section>

        <section aria-labelledby="about-x402">
            <h2 id="about-x402" class="text-xl font-bold text-ink">About the x402 protocol</h2>
            <div class="app-surface mt-4 p-5 sm:p-6">
                <p class="max-w-[68ch] text-sm leading-6 text-ink-muted"><a href="https://x402.org" target="_blank" rel="noopener noreferrer" class="app-link font-semibold">x402<x-ui.new-tab /></a> is an open, neutral standard for internet-native payments. It uses the HTTP <code class="font-semibold text-ink">402 Payment Required</code> status code for frictionless, programmatic payments, which suits AI agents and automated systems.</p>
                <dl class="mt-5 grid grid-cols-2 gap-3 lg:grid-cols-4">
                    @foreach($protocol as [$value, $label])
                        <div class="flex flex-col-reverse rounded-card border border-line bg-surface-subtle p-3 text-center">
                            <dt class="text-xs text-ink-muted">{{ $label }}</dt>
                            <dd class="tabular text-lg font-bold text-ink">{{ $value }}</dd>
                        </div>
                    @endforeach
                </dl>
            </div>
        </section>

        <section class="hero-canvas rounded-panel p-6 sm:p-8" aria-labelledby="donate-cta">
            <div class="relative flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 id="donate-cta" class="text-xl font-bold text-white">Every contribution makes a difference</h2>
                    <p class="mt-1 text-sm text-white/85">Developer, business or AI agent: thank you for supporting free EU VAT data.</p>
                </div>
                <div class="flex shrink-0 flex-wrap gap-3">
                    <a href="https://github.com/sponsors/eimkasp" target="_blank" rel="noopener noreferrer" class="app-button-inverse">
                        <x-ui.icon name="heart" class="size-4" />
                        Sponsor
                    <x-ui.new-tab /></a>
                    <a href="https://github.com/eimkasp/eu-vat.info" target="_blank" rel="noopener noreferrer" class="app-button-on-brand">
                        <x-ui.icon name="github" class="size-4" />
                        Star on GitHub
                    <x-ui.new-tab /></a>
                </div>
            </div>
        </section>
    </div>
</div>

<script data-cfasync="false">
function x402Test() {
    return {
        loading: false,
        result: null,
        error: null,

        async testEndpoint() {
            this.loading = true;
            this.result = null;
            this.error = null;

            try {
                const response = await fetch('{{ url("/api/x402/donate") }}', {
                    method: 'GET',
                    headers: { 'Accept': 'application/json' },
                });

                const status = response.status;
                let body = '';
                try {
                    const json = await response.json();
                    body = JSON.stringify(json, null, 2);
                } catch {
                    body = await response.text();
                }

                if (status === 402) {
                    const paymentHeader = response.headers.get('X-PAYMENT') || response.headers.get('PAYMENT-REQUIRED');
                    this.result = {
                        success: true,
                        status: status,
                        title: 'x402 is working correctly',
                        body: body,
                        explanation: 'The server returned HTTP 402 (Payment Required) as expected. An x402-compatible agent would now sign a USDC payment and retry the request with a PAYMENT-SIGNATURE header to complete the donation.'
                    };
                } else if (status === 200) {
                    this.result = {
                        success: true,
                        status: status,
                        title: 'Endpoint reachable (x402 may be disabled)',
                        body: body,
                        explanation: 'The server returned HTTP 200 instead of 402. The x402 middleware may be disabled in configuration. The donate endpoint is reachable but not requiring payment.'
                    };
                } else {
                    this.result = {
                        success: false,
                        status: status,
                        title: 'Unexpected response',
                        body: body,
                        explanation: 'Expected HTTP 402 (Payment Required) but received ' + status + '. Check that the x402 middleware is configured and the route is registered correctly.'
                    };
                }
            } catch (e) {
                this.error = 'Could not connect to the API endpoint. Error: ' + e.message;
            } finally {
                this.loading = false;
            }
        }
    };
}
</script>
