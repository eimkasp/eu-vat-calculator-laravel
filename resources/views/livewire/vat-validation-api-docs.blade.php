@section('seo')
    <x-seo-meta
        title="EU VAT Number Validation API — Free REST API Documentation"
        description="Free REST API to validate EU VAT numbers in real-time via the official VIES database. Single and batch validation, CORS headers, no API key required."
        type="website"
        :url="url()->current()">
        <x-json-ld :data="[
            '@type' => 'WebAPI',
            'name' => 'EU VAT Info API',
            'description' => 'Free REST API for EU VAT rates, VAT calculations and VIES VAT number validation. No API key required.',
            'url' => url()->current(),
            'documentation' => url()->current(),
            'isAccessibleForFree' => true,
            'provider' => ['@type' => 'Organization', 'name' => 'EU VAT Info', 'url' => url('/')],
            'subjectOf' => ['@type' => 'CreativeWork', 'name' => 'OpenAPI description', 'url' => url('/api/v1/openapi.json'), 'encodingFormat' => 'application/vnd.oai.openapi+json'],
        ]" />
        <x-json-ld :data="[
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                ['@type' => 'ListItem', 'position' => 1, 'name' => __('ui.site_name'), 'item' => url(locale_path('/'))],
                ['@type' => 'ListItem', 'position' => 2, 'name' => __('ui.nav.api'), 'item' => url()->current()],
            ],
        ]" />
    </x-seo-meta>
@endsection

<div x-data="{
    tab: 'single',
    simCountry: 'LT',
    simVat: '100019070512',
    loading: false,
    response: null,
    error: null,
    elapsed: null,
    async runRequest() {
        this.loading = true;
        this.response = null;
        this.error = null;
        this.elapsed = null;
        const t0 = performance.now();
        const body = this.tab === 'single'
            ? { country_code: this.simCountry, vat_number: this.simVat }
            : {
                validations: [
                    { country_code: this.simCountry, vat_number: this.simVat },
                    { country_code: 'DE', vat_number: '123456789' }
                ]
              };
        const endpoint = this.tab === 'single'
            ? '/api/vat/validation/validate'
            : '/api/vat/validation/batch';
        try {
            const res = await fetch(endpoint, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify(body)
            });
            const json = await res.json();
            this.elapsed = Math.round(performance.now() - t0);
            this.response = JSON.stringify(json, null, 2);
            if (!res.ok) this.error = this.response, this.response = null;
        } catch(e) {
            this.error = e.message;
        }
        this.loading = false;
    },
    get requestBody() {
        if (this.tab === 'single') {
            return JSON.stringify({ country_code: this.simCountry, vat_number: this.simVat }, null, 2);
        }
        return JSON.stringify({
            validations: [
                { country_code: this.simCountry, vat_number: this.simVat },
                { country_code: 'DE', vat_number: '123456789' }
            ]
        }, null, 2);
    },
    get currentEndpoint() {
        return this.tab === 'single' ? '/api/vat/validation/validate' : '/api/vat/validation/batch';
    }
}">
    <section class="hero-field">
        <x-hero-backdrop />
        <div class="app-container relative pb-10 pt-6 sm:pb-14 sm:pt-8">
            <x-site-breadcrumbs :items="[__('ui.vies_page.nav_title') => locale_path('/vat-number-validator'), 'API documentation' => '']" />
            <div class="mt-6 max-w-2xl">
                <h1 class="text-3xl font-semibold tracking-[-0.03em] text-ink sm:text-5xl sm:leading-[1.04]">EU VAT validation API</h1>
                <p class="mt-4 max-w-[54ch] text-base leading-7 text-ink-muted sm:text-lg">Validate EU VAT numbers programmatically against the official VIES database. Free to use, no API key and CORS enabled.</p>
                <ul class="mt-3 flex flex-wrap gap-x-5 gap-y-1.5 font-display text-[0.8125rem] font-medium text-ink-muted">
                    <li class="inline-flex items-center gap-1.5"><x-ui.icon name="code" class="size-3.5 text-action" />REST API</li>
                    <li class="inline-flex items-center gap-1.5"><x-ui.icon name="lock" class="size-3.5 text-action" />No auth required</li>
                </ul>
            </div>
            <dl class="mt-8 grid max-w-3xl grid-cols-1 divide-y divide-line overflow-hidden rounded-card border border-line bg-surface sm:grid-cols-3 sm:divide-x sm:divide-y-0">
                @foreach([['Free', 'No API key or account needed'], ['10 per batch', 'Validate up to 10 numbers at once'], ['27 countries', 'Every EU member state']] as [$value, $label])
                    <div class="flex flex-col-reverse px-5 py-4">
                        <dt class="mt-1 text-sm text-ink-muted">{{ $label }}</dt>
                        <dd class="tabular font-display text-2xl font-semibold tracking-[-0.02em] text-ink">{{ $value }}</dd>
                    </div>
                @endforeach
            </dl>
        </div>
    </section>

    <div class="app-container py-8 sm:py-10">
        <div class="grid grid-cols-1 items-start gap-8 lg:grid-cols-[minmax(0,1fr)_20rem]">
            <div class="min-w-0 space-y-10">
                <section id="base-url" class="scroll-mt-24" aria-labelledby="base-url-heading">
                    <h2 id="base-url-heading" class="text-xl font-semibold text-ink">Base URL</h2>
                    <div class="app-code mt-4 flex items-center gap-3 py-1.5 pl-4 pr-1.5">
                        <code class="min-w-0 flex-1 truncate font-mono text-sm text-syntax-ok">{{ $baseUrl }}</code>
                        <button type="button" class="pressable inline-flex h-8 shrink-0 items-center gap-1.5 rounded-control px-2.5 text-xs font-semibold text-white/80 hover:bg-white/10 hover:text-white" x-on:click="$copy(@js($baseUrl), 'Base URL copied')">
                            <x-ui.icon name="copy" class="size-3.5" />
                            {{ __('ui.calculator.copy') }}
                        </button>
                    </div>
                    <p class="mt-2 text-xs text-ink-muted">Every endpoint is relative to this base URL. Use HTTPS in production.</p>
                </section>

                <section id="endpoints" class="scroll-mt-24 space-y-4" aria-labelledby="endpoints-heading">
                    <h2 id="endpoints-heading" class="text-xl font-semibold text-ink">Endpoints</h2>

                    <article class="app-surface overflow-hidden" aria-labelledby="endpoint-validate">
                        <header class="flex flex-wrap items-center gap-3 border-b border-line bg-surface-subtle px-5 py-3.5">
                            <span class="app-badge bg-action-soft font-mono text-action-deep">POST</span>
                            <h3 id="endpoint-validate" class="font-mono text-sm font-semibold text-ink">/api/vat/validation/validate</h3>
                        </header>
                        <div class="space-y-5 p-5">
                            <p class="text-sm leading-6 text-ink-muted">Validate a single EU VAT number in real time against the VIES database. Results are cached for 7 days.</p>

                            <div>
                                <h4 id="request-body-heading" class="app-eyebrow">Request body</h4>
                                <div class="relative mt-2 overflow-x-auto rounded-card border border-line" tabindex="0" role="group" aria-labelledby="request-body-heading">
                                    <table class="app-table min-w-[34rem]">
                                        <thead>
                                            <tr>
                                                <th scope="col">Parameter</th>
                                                <th scope="col">Type</th>
                                                <th scope="col">Required</th>
                                                <th scope="col">Description</th>
                                            </tr>
                                        </thead>
                                        <tbody class="text-xs">
                                            @foreach([
                                                ['country_code', 'string', true, '2-letter ISO code, for example <code class="rounded-xs bg-surface-muted px-1 font-mono">LT</code> or <code class="rounded-xs bg-surface-muted px-1 font-mono">DE</code>. Greece uses <code class="rounded-xs bg-surface-muted px-1 font-mono">EL</code>.'],
                                                ['vat_number', 'string', true, 'The VAT number without the country prefix: <code class="rounded-xs bg-surface-muted px-1 font-mono">100019070512</code>, not <code class="rounded-xs bg-surface-muted px-1 font-mono">LT100019070512</code>.'],
                                                ['company_name', 'string', false, 'Expected company name for fuzzy-match verification.'],
                                                ['address', 'string', false, 'Expected address for additional verification.'],
                                            ] as [$field, $type, $required, $description])
                                                <tr>
                                                    <td><code class="rounded-xs bg-action-soft px-1.5 py-0.5 font-mono text-action-deep">{{ $field }}</code></td>
                                                    <td class="font-mono text-ink-muted">{{ $type }}</td>
                                                    <td>
                                                        @if($required)
                                                            <span class="font-semibold text-ink">Required</span>
                                                        @else
                                                            <span class="text-ink-quiet">Optional</span>
                                                        @endif
                                                    </td>
                                                    <td class="leading-5 text-ink-muted">{!! $description !!}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <div class="space-y-4">
                                <div class="min-w-0">
                                    <h4 class="app-eyebrow">Example request</h4>
                                    <pre tabindex="0" class="app-code mt-2 overflow-x-auto p-4 font-mono text-xs leading-5 text-white/75">curl -X POST {{ $baseUrl }}/api/vat/validation/validate \
  -H "Content-Type: application/json" \
  -d '<span class="text-syntax-literal">{"country_code": "LT", "vat_number": "100019070512"}</span>'</pre>
                                </div>
                                <div class="min-w-0">
                                    <h4 class="app-eyebrow">Example response</h4>
                                    <pre tabindex="0" class="app-code mt-2 overflow-x-auto p-4 font-mono text-xs leading-5 text-syntax-string">{
  "success": true,
  "data": {
    "valid": true,
    "country_code": "LT",
    "vat_number": "100019070512",
    "name": "UAB Company Name",
    "address": "Vilnius, Lithuania",
    "source": "vies",
    "request_identifier": "..."
  }
}</pre>
                                </div>
                            </div>
                        </div>
                    </article>

                    <article class="app-surface overflow-hidden" aria-labelledby="endpoint-batch">
                        <header class="flex flex-wrap items-center gap-3 border-b border-line bg-surface-subtle px-5 py-3.5">
                            <span class="app-badge bg-action-soft font-mono text-action-deep">POST</span>
                            <h3 id="endpoint-batch" class="font-mono text-sm font-semibold text-ink">/api/vat/validation/batch</h3>
                            <span class="app-badge ml-auto bg-surface-muted text-ink-muted">Up to 10 numbers</span>
                        </header>
                        <div class="space-y-5 p-5">
                            <p class="text-sm leading-6 text-ink-muted">Validate up to 10 VAT numbers in one request. Each number is checked against VIES independently.</p>
                            <div class="space-y-4">
                                <div class="min-w-0">
                                    <h4 class="app-eyebrow">Example request</h4>
                                    <pre tabindex="0" class="app-code mt-2 overflow-x-auto p-4 font-mono text-xs leading-5 text-syntax-literal">{
  "validations": [
    {
      "country_code": "LT",
      "vat_number": "100019070512"
    },
    {
      "country_code": "DE",
      "vat_number": "129274202"
    }
  ]
}</pre>
                                </div>
                                <div class="min-w-0">
                                    <h4 class="app-eyebrow">Example response</h4>
                                    <pre tabindex="0" class="app-code mt-2 overflow-x-auto p-4 font-mono text-xs leading-5 text-syntax-string">{
  "success": true,
  "total": 2,
  "data": [
    {
      "valid": true,
      "country_code": "LT",
      "vat_number": "100019070512",
      "name": "UAB ...",
      ...
    },
    { ... }
  ]
}</pre>
                                </div>
                            </div>
                        </div>
                    </article>

                    <article class="app-surface overflow-hidden" aria-labelledby="endpoint-health">
                        <header class="flex flex-wrap items-center gap-3 border-b border-line bg-surface-subtle px-5 py-3.5">
                            <span class="app-badge bg-success-soft font-mono text-success">GET</span>
                            <h3 id="endpoint-health" class="font-mono text-sm font-semibold text-ink">/api/vat/validation/health</h3>
                        </header>
                        <div class="space-y-4 p-5">
                            <p class="text-sm leading-6 text-ink-muted">Check the operational status of the VAT validation service.</p>
                            <pre tabindex="0" class="app-code overflow-x-auto p-4 font-mono text-xs leading-5 text-syntax-string">{
  "status": "operational",
  "service": "VAT VIES Validation API",
  "timestamp": "2026-04-07T10:00:00+00:00"
}</pre>
                        </div>
                    </article>
                </section>

                <section id="rates-api" class="scroll-mt-24 space-y-4" aria-labelledby="rates-api-heading">
                    <h2 id="rates-api-heading" class="text-xl font-semibold text-ink">VAT rates and calculator API</h2>
                    <p class="max-w-[72ch] text-sm leading-6 text-ink-muted">Every EU VAT rate and a calculator as JSON, with no API key. Rates follow the European Commission's data and the open dataset is synced every week; each country carries its <code class="rounded-xs bg-surface-muted px-1 font-mono">last_updated</code> time.</p>
                    <div class="app-surface relative overflow-x-auto" tabindex="0" role="group" aria-labelledby="rates-api-heading">
                        <table class="app-table min-w-[34rem]">
                            <thead>
                                <tr>
                                    <th scope="col" class="pl-5">Endpoint</th>
                                    <th scope="col" class="pr-5">Returns</th>
                                </tr>
                            </thead>
                            <tbody class="text-sm">
                                @foreach([
                                    ['/api/v1/countries', 'Every country with all rate types, currency and VAT number format.'],
                                    ['/api/v1/countries/{slug-or-iso}', 'One country, for example /api/v1/countries/germany or /api/v1/countries/DE.'],
                                    ['/api/v1/calculate?amount=100&country=DE&rate_type=standard&mode=add', 'Net, VAT and gross for an amount. mode=remove takes VAT out of a gross amount.'],
                                    ['/api/v1/openapi.json', 'The OpenAPI 3.1 description, for code generators and AI agents.'],
                                ] as [$path, $returns])
                                    <tr>
                                        <td class="pl-5 font-mono text-xs break-all text-ink"><span class="app-badge mr-2 bg-success-soft font-mono text-success">GET</span>{{ $path }}</td>
                                        <td class="pr-5 leading-5 text-ink-muted">{{ $returns }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="grid gap-4 lg:grid-cols-2">
                        <div class="min-w-0">
                            <h3 class="break-all font-mono text-xs font-medium text-ink-muted">GET /api/v1/countries/germany</h3>
                            <pre tabindex="0" class="app-code mt-2 overflow-x-auto p-4 font-mono text-xs leading-5 text-syntax-string">{
  "data": {
    "name": "Germany",
    "iso_code": "DE",
    "slug": "germany",
    "currency": "EUR",
    "rates": {
      "standard": 19,
      "reduced": 7,
      "reduced_rates": [7],
      "super_reduced": null,
      "parking": null
    },
    "vat_number": { "prefix": "DE", "format": "DE999999999" },
    "last_updated": "2026-10-12T19:39:01+00:00"
  }
}</pre>
                        </div>
                        <div class="min-w-0">
                            <h3 class="break-all font-mono text-xs font-medium text-ink-muted">GET /api/v1/calculate?amount=100&amp;country=DE</h3>
                            <pre tabindex="0" class="app-code mt-2 overflow-x-auto p-4 font-mono text-xs leading-5 text-syntax-string">{
  "data": {
    "country": "Germany",
    "rate_type": "standard",
    "rate": 19,
    "mode": "add",
    "amount": 100,
    "net": 100,
    "vat": 19,
    "gross": 119,
    "currency": "EUR"
  }
}</pre>
                        </div>
                    </div>
                </section>

                <section id="playground" class="scroll-mt-24" aria-labelledby="playground-heading">
                    <h2 id="playground-heading" class="text-xl font-semibold text-ink">Interactive playground</h2>
                    <div class="app-surface mt-4 space-y-4 p-5">
                        <div class="flex flex-wrap gap-2" role="group" aria-label="Request type">
                            <button type="button" class="app-chip" :class="tab === 'single' && 'app-chip-active'" :aria-pressed="(tab === 'single').toString()" aria-pressed="true" x-on:click="tab = 'single'; response = null; error = null">Single validation</button>
                            <button type="button" class="app-chip" :class="tab === 'batch' && 'app-chip-active'" :aria-pressed="(tab === 'batch').toString()" aria-pressed="false" x-on:click="tab = 'batch'; response = null; error = null">Batch demo</button>
                        </div>

                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-[8rem_minmax(0,1fr)]">
                            <div>
                                <label for="playground-country" class="mb-1.5 block font-mono text-xs font-semibold text-ink-muted">country_code</label>
                                <input id="playground-country" x-model="simCountry" type="text" maxlength="2" placeholder="LT" autocomplete="off" class="app-field font-mono uppercase">
                            </div>
                            <div>
                                <label for="playground-number" class="mb-1.5 block font-mono text-xs font-semibold text-ink-muted">vat_number</label>
                                <input id="playground-number" x-model="simVat" type="text" placeholder="100019070512" autocomplete="off" class="app-field font-mono" x-on:keydown.enter="runRequest()">
                            </div>
                        </div>
                        <p x-cloak x-show="tab === 'batch'" class="app-note">
                            <x-ui.icon name="info" class="mt-1 size-4 text-action" />
                            <span>The batch demo validates the number above together with a fixed German example, so you can see the multi-result format.</span>
                        </p>

                        <div class="app-code overflow-hidden">
                            <div class="flex items-center justify-between gap-3 border-b border-white/10 py-1.5 pl-4 pr-1.5">
                                <p class="truncate font-mono text-xs"><span class="font-semibold text-syntax-ok">POST</span> <span class="text-white/60" x-text="currentEndpoint">/api/vat/validation/validate</span></p>
                                <button type="button" x-on:click="runRequest()" :disabled="loading" :aria-busy="loading.toString()" class="app-button-primary h-8 min-h-8 shrink-0 px-3 text-xs">
                                    <x-ui.icon name="play" class="size-3.5" x-show="!loading" />
                                    <span x-cloak x-show="loading" class="block size-3.5 animate-spin rounded-full border-2 border-white/30 border-t-white" aria-hidden="true"></span>
                                    <span x-text="loading ? 'Sending…' : 'Send request'">Send request</span>
                                </button>
                            </div>
                            <pre tabindex="0" class="overflow-x-auto p-4 font-mono text-xs leading-5 text-syntax-literal" x-text="requestBody"></pre>
                            <div class="border-t border-white/10" aria-live="polite">
                                <p x-show="!response && !error && !loading" class="p-4 font-mono text-xs text-syntax-comment">// The response appears here after you send the request.</p>
                                <p x-cloak x-show="loading" class="flex items-center gap-2 p-4 font-mono text-xs text-white/60">
                                    <span class="block size-3.5 animate-spin rounded-full border-2 border-syntax-keyword/30 border-t-syntax-keyword" aria-hidden="true"></span>
                                    Sending the request to VIES…
                                </p>
                                <div x-cloak x-show="response">
                                    <div class="flex items-center justify-between gap-3 border-b border-white/10 py-1.5 pl-4 pr-1.5">
                                        <p class="font-mono text-xs"><span class="font-semibold text-syntax-ok">200 OK</span> <span class="text-white/45" x-text="elapsed ? elapsed + ' ms' : ''"></span></p>
                                        <button type="button" class="pressable inline-flex h-8 items-center gap-1.5 rounded-control px-2.5 text-xs font-semibold text-white/80 hover:bg-white/10 hover:text-white" x-on:click="$copy(response, 'Response copied')">
                                            <x-ui.icon name="copy" class="size-3.5" />
                                            {{ __('ui.calculator.copy') }}
                                        </button>
                                    </div>
                                    <pre tabindex="0" class="overflow-x-auto p-4 font-mono text-xs leading-5 text-syntax-string" x-text="response"></pre>
                                </div>
                                <div x-cloak x-show="error" class="p-4">
                                    <p class="font-mono text-xs font-semibold text-syntax-error">Error</p>
                                    <pre tabindex="0" class="mt-2 overflow-x-auto font-mono text-xs leading-5 text-syntax-error" x-text="error"></pre>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <section id="response-fields" class="scroll-mt-24" aria-labelledby="response-fields-heading">
                    <h2 id="response-fields-heading" class="text-xl font-semibold text-ink">Response fields</h2>
                    <div class="app-surface relative mt-4 overflow-x-auto" tabindex="0" role="group" aria-labelledby="response-fields-heading">
                        <table class="app-table min-w-[34rem]">
                            <thead>
                                <tr>
                                    <th scope="col" class="pl-5">Field</th>
                                    <th scope="col">Type</th>
                                    <th scope="col" class="pr-5">Description</th>
                                </tr>
                            </thead>
                            <tbody class="text-xs">
                                @foreach([
                                    ['success', 'boolean', 'Always <code class="rounded-xs bg-surface-muted px-1 font-mono">true</code> for 200 responses.'],
                                    ['data.valid', 'boolean', 'Whether the VAT number is currently active in VIES.'],
                                    ['data.country_code', 'string', 'The 2-letter country code, echoed back.'],
                                    ['data.vat_number', 'string', 'The VAT number as validated, without the prefix.'],
                                    ['data.name', 'string|null', 'Company name returned by VIES. May be <code class="rounded-xs bg-surface-muted px-1 font-mono">N/A</code> when the member state withholds it.'],
                                    ['data.address', 'string|null', 'Registered address. Some member states withhold it.'],
                                    ['data.source', 'string', '<code class="rounded-xs bg-surface-muted px-1 font-mono">vies</code> for a live lookup, or <code class="rounded-xs bg-surface-muted px-1 font-mono">cache</code> and <code class="rounded-xs bg-surface-muted px-1 font-mono">database</code> for a cached result.'],
                                    ['data.request_identifier', 'string|null', 'VIES consultation reference number for your audit trail.'],
                                ] as [$field, $type, $description])
                                    <tr>
                                        <td class="pl-5"><code class="whitespace-nowrap rounded-xs bg-surface-muted px-1.5 py-0.5 font-mono text-ink">{{ $field }}</code></td>
                                        <td class="whitespace-nowrap font-mono text-ink-muted">{{ $type }}</td>
                                        <td class="pr-5 leading-5 text-ink-muted">{!! $description !!}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </section>

                <section id="errors" class="scroll-mt-24" aria-labelledby="errors-heading">
                    <h2 id="errors-heading" class="text-xl font-semibold text-ink">Error responses</h2>
                    <div class="app-surface relative mt-4 overflow-x-auto" tabindex="0" role="group" aria-labelledby="errors-heading">
                        <table class="app-table min-w-[28rem]">
                            <thead>
                                <tr>
                                    <th scope="col" class="pl-5">HTTP status</th>
                                    <th scope="col" class="pr-5">When</th>
                                </tr>
                            </thead>
                            <tbody class="text-xs">
                                @foreach([
                                    ['422', 'Validation failed: <code class="rounded-xs bg-surface-muted px-1 font-mono">country_code</code> or <code class="rounded-xs bg-surface-muted px-1 font-mono">vat_number</code> is missing or invalid. The <code class="rounded-xs bg-surface-muted px-1 font-mono">errors</code> field explains why.'],
                                    ['429', 'Too many requests: the fair-use limit was reached. Retry after a short pause.'],
                                    ['503', 'The VIES system is temporarily unavailable. Check <a href="https://ec.europa.eu/taxation_customs/vies" target="_blank" rel="noopener noreferrer" class="app-link">the VIES status page<span class="sr-only" data-markdown-skip> ('.e(__('ui.opens_new_tab')).')</span></a>.'],
                                ] as [$status, $description])
                                    <tr>
                                        <td class="pl-5"><span class="app-badge bg-danger-soft font-mono text-danger">{{ $status }}</span></td>
                                        <td class="pr-5 leading-5 text-ink-muted">{!! $description !!}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </section>

                <section id="examples" class="scroll-mt-24" x-data="{ lang: 'curl' }" aria-labelledby="examples-heading">
                    <h2 id="examples-heading" class="text-xl font-semibold text-ink">Code examples</h2>
                    <div class="mt-4 flex flex-wrap gap-2" role="group" aria-label="Language">
                        @foreach(['curl' => 'cURL', 'javascript' => 'JavaScript', 'php' => 'PHP', 'python' => 'Python'] as $lang => $label)
                            <button type="button" @class(['app-chip', 'app-chip-active' => $loop->first]) :class="{ 'app-chip-active': lang === @js($lang) }" aria-pressed="{{ $loop->first ? 'true' : 'false' }}" :aria-pressed="(lang === @js($lang)).toString()" x-on:click="lang = @js($lang)">{{ $label }}</button>
                        @endforeach
                    </div>
                    @foreach(['curl' => 'cURL', 'javascript' => 'JavaScript', 'php' => 'PHP', 'python' => 'Python'] as $lang => $label)
                        <div class="app-code mt-3 overflow-hidden" x-show="lang === @js($lang)" @unless($loop->first) x-cloak @endunless>
                            <div class="flex items-center justify-between gap-3 border-b border-white/10 py-1.5 pl-4 pr-1.5">
                                <p class="font-mono text-xs text-syntax-comment">{{ $label }}</p>
                                <button type="button" class="pressable inline-flex h-8 items-center gap-1.5 rounded-control px-2.5 text-xs font-semibold text-white/80 hover:bg-white/10 hover:text-white" x-on:click="$copy($refs.example_{{ $lang }}.textContent.trim(), 'Code copied')">
                                    <x-ui.icon name="copy" class="size-3.5" />
                                    {{ __('ui.calculator.copy') }}
                                </button>
                            </div>
                            @switch($lang)
                                @case('curl')
<pre tabindex="0" x-ref="example_curl" class="overflow-x-auto p-4 font-mono text-xs leading-relaxed text-white/75"><code class="text-syntax-function">curl</code> -X POST {{ $baseUrl }}/api/vat/validation/validate \
  -H <code class="text-syntax-string">"Content-Type: application/json"</code> \
  -d <code class="text-syntax-literal">'{"country_code":"LT","vat_number":"100019070512"}'</code></pre>
                                    @break
                                @case('javascript')
<pre tabindex="0" x-ref="example_javascript" class="overflow-x-auto p-4 font-mono text-xs leading-relaxed text-white/75"><code class="text-syntax-keyword">const</code> <code class="text-white/75">response</code> = <code class="text-syntax-keyword">await</code> <code class="text-syntax-function">fetch</code>(<code class="text-syntax-string">'{{ $baseUrl }}/api/vat/validation/validate'</code>, {
  <code class="text-syntax-function">method</code>: <code class="text-syntax-string">'POST'</code>,
  <code class="text-syntax-function">headers</code>: { <code class="text-syntax-string">'Content-Type'</code>: <code class="text-syntax-string">'application/json'</code> },
  <code class="text-syntax-function">body</code>: <code class="text-syntax-keyword">JSON</code>.<code class="text-syntax-function">stringify</code>({
    <code class="text-syntax-function">country_code</code>: <code class="text-syntax-string">'LT'</code>,
    <code class="text-syntax-function">vat_number</code>: <code class="text-syntax-string">'100019070512'</code>
  })
});
<code class="text-syntax-keyword">const</code> <code class="text-white/75">data</code> = <code class="text-syntax-keyword">await</code> <code class="text-white/75">response</code>.<code class="text-syntax-function">json</code>();
<code class="text-syntax-keyword">console</code>.<code class="text-syntax-function">log</code>(<code class="text-white/75">data</code>.<code class="text-white/75">data</code>.<code class="text-white/75">valid</code>); <code class="text-white/45">// true</code></pre>
                                    @break
                                @case('php')
<pre tabindex="0" x-ref="example_php" class="overflow-x-auto p-4 font-mono text-xs leading-relaxed text-white/75"><code class="text-syntax-keyword">$response</code> = <code class="text-syntax-function">Http</code>::<code class="text-syntax-function">post</code>(<code class="text-syntax-string">'{{ $baseUrl }}/api/vat/validation/validate'</code>, [
    <code class="text-syntax-string">'country_code'</code> => <code class="text-syntax-string">'LT'</code>,
    <code class="text-syntax-string">'vat_number'</code>  => <code class="text-syntax-string">'100019070512'</code>,
]);

<code class="text-syntax-keyword">$data</code> = <code class="text-syntax-keyword">$response</code>-><code class="text-syntax-function">json</code>(<code class="text-syntax-string">'data'</code>);
<code class="text-syntax-keyword">$isValid</code> = <code class="text-syntax-keyword">$data</code>[<code class="text-syntax-string">'valid'</code>]; <code class="text-white/45">// true</code></pre>
                                    @break
                                @case('python')
<pre tabindex="0" x-ref="example_python" class="overflow-x-auto p-4 font-mono text-xs leading-relaxed text-white/75"><code class="text-syntax-keyword">import</code> <code class="text-white/75">requests</code>

<code class="text-syntax-keyword">res</code> = <code class="text-white/75">requests</code>.<code class="text-syntax-function">post</code>(
    <code class="text-syntax-string">"{{ $baseUrl }}/api/vat/validation/validate"</code>,
    <code class="text-syntax-function">json</code>={<code class="text-syntax-string">"country_code"</code>: <code class="text-syntax-string">"LT"</code>, <code class="text-syntax-string">"vat_number"</code>: <code class="text-syntax-string">"100019070512"</code>}
)
<code class="text-syntax-keyword">data</code> = <code class="text-white/75">res</code>.<code class="text-syntax-function">json</code>()[<code class="text-syntax-string">"data"</code>]
<code class="text-syntax-keyword">print</code>(<code class="text-white/75">data</code>[<code class="text-syntax-string">"valid"</code>])  <code class="text-white/45"># True</code></pre>
                                    @break
                            @endswitch
                        </div>
                    @endforeach
                </section>
            </div>

            <aside class="space-y-4 lg:sticky lg:top-24">
                <nav class="app-surface p-5" aria-labelledby="docs-toc">
                    <h2 id="docs-toc" class="text-sm font-semibold text-ink">On this page</h2>
                    <ul class="-mx-2 mt-2 space-y-0.5 text-sm">
                        @foreach(['base-url' => 'Base URL', 'endpoints' => 'Endpoints', 'rates-api' => 'VAT rates and calculator API', 'playground' => 'Interactive playground', 'response-fields' => 'Response fields', 'errors' => 'Error responses', 'examples' => 'Code examples'] as $anchor => $label)
                            <li>
                                <a href="#{{ $anchor }}" class="flex min-h-9 items-center gap-2 rounded-control px-2 text-ink-muted transition-colors hover:bg-surface-subtle hover:text-ink">
                                    <x-ui.icon name="chevron-right" class="size-3.5 text-ink-quiet" />
                                    {{ $label }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </nav>

                <section class="app-surface p-5" aria-labelledby="docs-reference">
                    <h2 id="docs-reference" class="text-sm font-semibold text-ink">Quick reference</h2>
                    <ul class="mt-3 space-y-2">
                        @foreach([['POST', '/api/vat/validation/validate'], ['POST', '/api/vat/validation/batch'], ['GET', '/api/vat/validation/health']] as [$method, $path])
                            <li class="flex items-center gap-2">
                                <span @class(['app-badge w-12 shrink-0 justify-center font-mono', 'bg-action-soft text-action-deep' => $method === 'POST', 'bg-success-soft text-success' => $method === 'GET'])>{{ $method }}</span>
                                <code class="min-w-0 break-all font-mono text-xs text-ink-muted">{{ $path }}</code>
                            </li>
                        @endforeach
                    </ul>
                </section>

                <section class="app-surface p-5" aria-labelledby="docs-rates">
                    <h2 id="docs-rates" class="text-sm font-semibold text-ink">VAT rates API</h2>
                    <p class="mt-1.5 text-xs leading-5 text-ink-muted">Need rates rather than validation? The v1 API returns every rate type, and its OpenAPI description works with code generators.</p>
                    <ul class="mt-3 space-y-2">
                        <li class="flex items-center gap-2">
                            <span class="app-badge w-12 shrink-0 justify-center bg-success-soft font-mono text-success">GET</span>
                            <code class="min-w-0 break-all font-mono text-xs text-ink-muted">/api/v1/countries</code>
                        </li>
                    </ul>
                    <p class="mt-3 flex flex-wrap gap-x-4 gap-y-1 text-xs font-semibold">
                        <a href="/api/v1/countries" class="inline-flex items-center gap-1 text-action hover:text-action-deep hover:underline">Open the API <x-ui.icon name="chevron-right" class="size-3" /></a>
                        <a href="/api/v1/openapi.json" class="inline-flex items-center gap-1 text-action hover:text-action-deep hover:underline">OpenAPI <x-ui.icon name="chevron-right" class="size-3" /></a>
                    </p>
                </section>

                <nav class="app-surface p-5" aria-labelledby="docs-related">
                    <h2 id="docs-related" class="text-sm font-semibold text-ink">Related</h2>
                    <ul class="-mx-2 mt-2 space-y-0.5 text-sm">
                        @foreach([
                            ['shield-check', __('ui.nav.vat_number_validator'), locale_path('/vat-number-validator')],
                            ['sparkles', __('ui.footer.mcp_server'), locale_path('/mcp-server')],
                            ['grid', __('ui.nav.all_tools'), locale_path('/tools')],
                        ] as [$icon, $label, $href])
                            <li>
                                <a href="{{ $href }}" class="flex min-h-9 items-center gap-2.5 rounded-control px-2 font-medium text-ink transition-colors hover:bg-surface-subtle hover:text-action">
                                    <x-ui.icon :name="$icon" class="size-4 text-action" />
                                    {{ $label }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </nav>
            </aside>
        </div>
    </div>
</div>
