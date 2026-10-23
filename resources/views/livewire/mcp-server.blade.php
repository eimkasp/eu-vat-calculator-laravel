@php
    $json = fn (array $value) => json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    $clients = [
        'claude' => ['label' => 'Claude', 'steps' => [__('ui.mcp_page.claude_step_1'), __('ui.mcp_page.claude_step_2'), __('ui.mcp_page.claude_step_3')]],
        'claude_code' => ['label' => 'Claude Code', 'file' => 'Terminal', 'code' => 'claude mcp add --transport http eu-vat-info '.$endpoint, 'steps' => [__('ui.mcp_page.claude_code_step_1'), __('ui.mcp_page.claude_code_step_2')]],
        'vscode' => ['label' => 'VS Code', 'file' => '.vscode/mcp.json', 'code' => $json(['servers' => ['eu-vat-info' => ['type' => 'http', 'url' => $endpoint]]]), 'steps' => [__('ui.mcp_page.vscode_step_1'), __('ui.mcp_page.vscode_step_2')]],
        'cursor' => ['label' => 'Cursor', 'file' => 'mcp.json', 'code' => $json(['mcpServers' => ['eu-vat-info' => ['url' => $endpoint]]]), 'steps' => [__('ui.mcp_page.cursor_step_1'), __('ui.mcp_page.cursor_step_2')]],
        'other' => ['label' => __('ui.mcp_page.client_other'), 'file' => 'JSON', 'code' => $json(['mcpServers' => ['eu-vat-info' => ['type' => 'http', 'url' => $endpoint]]]), 'steps' => [__('ui.mcp_page.other_step_1'), __('ui.mcp_page.other_step_2')]],
    ];
    $icons = [
        'get_all_vat_rates' => 'grid',
        'get_country_vat_rate' => 'map-pin',
        'calculate_vat' => 'calculator',
        'compare_vat_rates' => 'trending-up',
        'get_vat_rate_changes' => 'history',
        'validate_vat_number' => 'shield-check',
    ];
    $curl = "curl -X POST {$endpoint} \\\n  -H \"Content-Type: application/json\" \\\n  -H \"Accept: application/json, text/event-stream\" \\\n  -d '".json_encode($examples['call'], JSON_UNESCAPED_SLASHES)."'";
    $faqs = collect(range(1, 5))->map(fn (int $index) => ['q' => __("ui.mcp_page.faq_{$index}_q"), 'a' => __("ui.mcp_page.faq_{$index}_a")]);
    $pageUrl = url(locale_path('/mcp-server'));
@endphp

@section('seo')
    <x-seo-meta :title="__('ui.mcp_page.meta_title')" :description="__('ui.mcp_page.meta_desc')" :url="$pageUrl">
        <x-json-ld :data="[
            '@type' => 'WebAPI',
            'name' => __('ui.mcp_page.title'),
            'description' => __('ui.mcp_page.meta_desc'),
            'url' => $pageUrl,
            'documentation' => $pageUrl,
            'isAccessibleForFree' => true,
            'inLanguage' => app()->getLocale(),
            'provider' => ['@type' => 'Organization', 'name' => 'EU VAT Info', 'url' => url('/')],
            'potentialAction' => ['@type' => 'ConsumeAction', 'target' => $endpoint],
        ]" />
        <x-json-ld :data="[
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                ['@type' => 'ListItem', 'position' => 1, 'name' => __('ui.site_name'), 'item' => url(locale_path('/'))],
                ['@type' => 'ListItem', 'position' => 2, 'name' => __('ui.sitemap.mcp_server'), 'item' => $pageUrl],
            ],
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
    <section class="hero-field" aria-labelledby="mcp-heading">
        <x-hero-backdrop />
        <div class="app-container relative pb-10 pt-6 sm:pb-14 sm:pt-8">
            <x-site-breadcrumbs :items="[__('ui.sitemap.mcp_server') => '']" />

            <div class="mb-8 mt-6 max-w-2xl">
                <h1 id="mcp-heading" class="text-3xl font-semibold tracking-[-0.03em] text-ink sm:text-5xl sm:leading-[1.04]">{{ __('ui.mcp_page.title') }}</h1>
                <p class="mt-4 max-w-[54ch] text-base leading-7 text-ink-muted sm:text-lg">{{ __('ui.mcp_page.lede') }}</p>
                <p class="mt-3 inline-flex items-center gap-1.5 font-display text-[0.8125rem] font-medium text-ink-muted">
                    <x-ui.icon name="puzzle" class="size-3.5 text-action" />
                    {{ __('ui.mcp_page.kicker') }}
                </p>
            </div>

            <div class="app-surface-raised max-w-4xl overflow-hidden text-ink" x-data="{
                client: 'claude',
                clients: @js(array_keys($clients)),
                move(step) {
                    this.client = this.clients[(this.clients.indexOf(this.client) + step + this.clients.length) % this.clients.length];
                    this.$nextTick(() => document.getElementById('mcp-tab-' + this.client)?.focus());
                },
            }">
                <div class="border-b border-line p-5 sm:p-7">
                    <p id="mcp-endpoint-label" class="mb-1.5 block font-display text-[0.8125rem] font-medium text-ink-muted">{{ __('ui.mcp_page.endpoint_label') }}</p>
                    <div class="flex flex-col gap-2 sm:flex-row">
                        <code id="mcp-endpoint" class="app-field flex min-w-0 flex-1 select-all items-center font-mono text-[0.8125rem] break-all sm:text-sm">{{ $endpoint }}</code>
                        <button type="button" class="app-button-primary shrink-0" x-on:click="$copy(@js($endpoint), @js(__('ui.mcp_page.copied')))">
                            <x-ui.icon name="copy" class="size-4" />
                            {{ __('ui.mcp_page.copy_url') }}
                        </button>
                    </div>
                    <ul class="mt-3 flex flex-wrap gap-2">
                        <li class="app-badge bg-action-soft text-action-deep"><x-ui.icon name="zap" class="size-3.5" />{{ __('ui.mcp_page.badge_transport') }}</li>
                        <li class="app-badge bg-success-soft text-success"><x-ui.icon name="check-circle" class="size-3.5" />{{ __('ui.mcp_page.badge_no_auth') }}</li>
                        <li class="app-badge bg-surface-muted text-ink-muted"><x-ui.icon name="lock" class="size-3.5" />{{ __('ui.mcp_page.badge_read_only') }}</li>
                    </ul>
                </div>

                <div class="bg-surface-subtle p-5 sm:p-7">
                    <h2 class="text-lg font-semibold text-ink">{{ __('ui.mcp_page.connect_heading') }}</h2>
                    <p class="mt-1 text-sm leading-6 text-ink-muted">{{ __('ui.mcp_page.connect_desc') }}</p>

                    <div role="tablist" aria-label="{{ __('ui.mcp_page.clients_label') }}" class="mt-4 flex flex-wrap gap-2" x-on:keydown.arrow-right.prevent="move(1)" x-on:keydown.arrow-left.prevent="move(-1)">
                        @foreach($clients as $key => $client)
                            <button
                                type="button"
                                role="tab"
                                id="mcp-tab-{{ $key }}"
                                aria-controls="mcp-panel-{{ $key }}"
                                aria-selected="{{ $loop->first ? 'true' : 'false' }}"
                                tabindex="{{ $loop->first ? 0 : -1 }}"
                                :aria-selected="(client === @js($key)).toString()"
                                :tabindex="client === @js($key) ? 0 : -1"
                                @class(['app-chip', 'app-chip-active' => $loop->first])
                                :class="{ 'app-chip-active': client === @js($key) }"
                                x-on:click="client = @js($key)"
                            >{{ $client['label'] }}</button>
                        @endforeach
                    </div>

                    @foreach($clients as $key => $client)
                        <div role="tabpanel" id="mcp-panel-{{ $key }}" aria-labelledby="mcp-tab-{{ $key }}" tabindex="0" class="mt-5" x-show="client === @js($key)" @unless($loop->first) x-cloak @endunless>
                            <ol class="space-y-3">
                                @foreach($client['steps'] as $index => $step)
                                    <li class="flex gap-3">
                                        <span class="flex size-7 shrink-0 items-center justify-center rounded-full bg-action-soft font-display text-xs font-semibold text-action-deep">{{ $index + 1 }}</span>
                                        <div class="min-w-0 flex-1 pt-0.5">
                                            <p class="text-sm leading-6 text-ink">{{ $step }}</p>
                                            @if($index === 0 && isset($client['code']))
                                                <div class="app-code mt-3">
                                                    <div class="flex items-center justify-between gap-3 border-b border-white/10 py-1.5 pl-4 pr-1.5">
                                                        <p class="truncate font-mono text-xs text-syntax-comment">{{ $client['file'] }}</p>
                                                        <button type="button" class="pressable inline-flex h-8 shrink-0 items-center gap-1.5 rounded-control px-2.5 text-xs font-semibold text-white/80 hover:bg-white/10 hover:text-white" x-on:click="$copy(@js($client['code']), @js(__('ui.mcp_page.code_copied')))">
                                                            <x-ui.icon name="copy" class="size-3.5" />
                                                            {{ __('ui.calculator.copy') }}
                                                        </button>
                                                    </div>
                                                    <pre tabindex="0" class="overflow-x-auto p-4 font-mono text-xs leading-5 text-syntax-string">{{ $client['code'] }}</pre>
                                                </div>
                                            @endif
                                        </div>
                                    </li>
                                @endforeach
                            </ol>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    <div class="app-container space-y-12 py-10 sm:py-12">
        <section aria-labelledby="mcp-tools">
            <div class="max-w-3xl">
                <h2 id="mcp-tools" class="text-2xl font-semibold tracking-[-0.02em] text-ink">{{ __('ui.mcp_page.tools_heading') }}</h2>
                <p class="mt-2 text-base leading-7 text-ink-muted">{{ __('ui.mcp_page.tools_desc') }}</p>
            </div>

            <ul class="mt-6 grid gap-4 md:grid-cols-2">
                @foreach($tools as $tool)
                    @php
                        $properties = (array) $tool['inputSchema']['properties'];
                        $required = $tool['inputSchema']['required'] ?? [];
                    @endphp
                    <li class="app-surface flex flex-col p-5 sm:p-6">
                        <div class="flex items-start gap-3">
                            <span class="grid size-10 shrink-0 place-items-center rounded-control bg-action-soft text-action">
                                <x-ui.icon :name="$icons[$tool['name']] ?? 'zap'" class="size-5" />
                            </span>
                            <div class="min-w-0 flex-1">
                                <h3 class="font-semibold text-ink">{{ __('ui.mcp_page.tools.'.$tool['name'].'.title') }}</h3>
                                <p class="font-mono text-xs break-all text-ink-muted">{{ $tool['name'] }}</p>
                            </div>
                            @if($tool['annotations']['openWorldHint'])
                                <span class="app-badge shrink-0 bg-warning-soft text-warning"><x-ui.icon name="globe" class="size-3.5" />{{ __('ui.mcp_page.uses_vies') }}</span>
                            @endif
                        </div>

                        <p class="mt-3 text-sm leading-6 text-ink-muted">{{ __('ui.mcp_page.tools.'.$tool['name'].'.desc') }}</p>

                        <p class="mt-4 font-display text-[0.8125rem] font-medium text-ink-muted">{{ __('ui.mcp_page.parameters') }}</p>
                        @if($properties === [])
                            <p class="mt-1.5 text-sm text-ink-quiet">{{ __('ui.mcp_page.no_parameters') }}</p>
                        @else
                            <ul class="mt-2 flex flex-wrap gap-1.5">
                                @foreach($properties as $name => $schema)
                                    <li class="app-badge bg-surface-muted font-medium text-ink">
                                        <span class="font-mono">{{ $name }}</span>
                                        <span class="font-mono text-ink-quiet">{{ $schema['type'] }}</span>
                                        <span class="text-ink-quiet">{{ in_array($name, $required, true) ? __('ui.mcp_page.required') : __('ui.mcp_page.optional') }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        @endif

                        <div class="mt-auto pt-4">
                            <p class="app-note">
                                <x-ui.icon name="sparkles" class="mt-1 size-4 shrink-0 text-action" />
                                <span><span class="font-semibold text-ink">{{ __('ui.mcp_page.try_asking') }}:</span> {{ __('ui.mcp_page.tools.'.$tool['name'].'.prompt') }}</span>
                            </p>
                        </div>
                    </li>
                @endforeach
            </ul>
        </section>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-[minmax(0,1.35fr)_minmax(0,1fr)]">
            <section class="app-surface p-5 sm:p-6" aria-labelledby="mcp-try" x-data="mcpPlayground({ endpoint: @js(route('api.mcp', absolute: false)), examples: @js($examples) })">
                <h2 id="mcp-try" class="text-lg font-semibold text-ink">{{ __('ui.mcp_page.try_heading') }}</h2>
                <p class="mt-1 text-sm leading-6 text-ink-muted">{{ __('ui.mcp_page.try_desc') }}</p>

                <div role="radiogroup" aria-label="{{ __('ui.mcp_page.examples_label') }}" @keydown.arrow-right.prevent="$rove($event)" @keydown.arrow-down.prevent="$rove($event)" @keydown.arrow-left.prevent="$rove($event)" @keydown.arrow-up.prevent="$rove($event)" class="mt-4 flex flex-wrap gap-2">
                    @foreach(['initialize' => __('ui.mcp_page.example_initialize'), 'list' => __('ui.mcp_page.example_list'), 'call' => __('ui.mcp_page.example_call')] as $key => $label)
                        <button
                            type="button"
                            role="radio"
                            aria-checked="{{ $loop->first ? 'true' : 'false' }}"
                            :aria-checked="(active === @js($key)).toString()"
                            tabindex="{{ $loop->first ? '0' : '-1' }}"
                            :tabindex="active === @js($key) ? 0 : -1"
                            @class(['app-chip', 'app-chip-active' => $loop->first])
                            :class="{ 'app-chip-active': active === @js($key) }"
                            x-on:click="select(@js($key))"
                        >{{ $label }}</button>
                    @endforeach
                </div>

                <div class="app-code mt-4">
                    <div class="flex items-center justify-between gap-3 border-b border-white/10 px-4 py-2">
                        <p class="truncate font-mono text-xs text-syntax-comment"><span class="font-semibold text-syntax-ok">POST</span> /api/mcp</p>
                        <button type="button" x-on:click="send()" :disabled="loading" class="pressable inline-flex h-8 shrink-0 items-center gap-1.5 rounded-control bg-button px-3.5 text-xs font-semibold text-white hover:bg-button-hover disabled:cursor-not-allowed disabled:opacity-60">
                            <x-ui.icon name="play" class="size-3.5" x-show="!loading" />
                            <span x-cloak x-show="loading" class="size-3.5 animate-spin rounded-full border-2 border-white/40 border-t-white" aria-hidden="true"></span>
                            <span x-text="loading ? @js(__('ui.vies_page.api_sending')) : @js(__('ui.vies_page.api_send'))">{{ __('ui.vies_page.api_send') }}</span>
                        </button>
                    </div>
                    <pre tabindex="0" class="max-h-64 overflow-auto p-4 font-mono text-xs leading-5 text-syntax-literal" x-text="body">{{ $json($examples['initialize']) }}</pre>
                    <div class="border-t border-white/10">
                        <p class="sr-only" role="status" x-text="status === null ? '' : (status === 0 ? @js(__('ui.mcp_page.network_error')) : 'HTTP ' + status + (elapsed !== null ? ', ' + elapsed + ' ms' : ''))"></p>
                        <div x-show="status !== null" x-cloak class="flex items-center justify-between px-4 pt-3 font-mono text-xs">
                            <span :class="ok ? 'text-syntax-ok' : 'text-syntax-error'" x-text="status === 0 ? @js(__('ui.mcp_page.network_error')) : 'HTTP ' + status"></span>
                            <span class="text-syntax-comment" x-text="elapsed !== null ? elapsed + ' ms' : ''"></span>
                        </div>
                        <pre tabindex="0" x-show="response" x-cloak class="max-h-96 overflow-auto p-4 font-mono text-xs leading-5" :class="ok ? 'text-syntax-string' : 'text-syntax-error'" x-text="response"></pre>
                        <p x-show="!response && !loading" class="p-4 font-mono text-xs text-syntax-comment">{{ __('ui.vies_page.api_response_placeholder') }}</p>
                    </div>
                </div>

                <h3 class="mt-6 text-sm font-semibold text-ink">{{ __('ui.mcp_page.curl_heading') }}</h3>
                <div class="app-code mt-2">
                    <div class="flex items-center justify-between gap-3 border-b border-white/10 py-1.5 pl-4 pr-1.5">
                        <p class="truncate font-mono text-xs text-syntax-comment">curl</p>
                        <button type="button" class="pressable inline-flex h-8 shrink-0 items-center gap-1.5 rounded-control px-2.5 text-xs font-semibold text-white/80 hover:bg-white/10 hover:text-white" x-on:click="$copy(@js($curl), @js(__('ui.mcp_page.code_copied')))">
                            <x-ui.icon name="copy" class="size-3.5" />
                            {{ __('ui.calculator.copy') }}
                        </button>
                    </div>
                    <pre tabindex="0" class="overflow-x-auto p-4 font-mono text-xs leading-5 text-syntax-string">{{ $curl }}</pre>
                </div>
            </section>

            <section class="app-surface p-5 sm:p-6" aria-labelledby="mcp-details">
                <h2 id="mcp-details" class="text-lg font-semibold text-ink">{{ __('ui.mcp_page.details_heading') }}</h2>
                <dl class="mt-3 divide-y divide-line text-sm">
                    @foreach([
                        [__('ui.mcp_page.detail_endpoint'), $endpoint, true],
                        [__('ui.mcp_page.detail_transport'), __('ui.mcp_page.transport_value'), false],
                        [__('ui.mcp_page.detail_protocol'), implode(', ', $protocolVersions), true],
                        [__('ui.mcp_page.detail_auth'), __('ui.mcp_page.auth_value'), false],
                        [__('ui.mcp_page.detail_access'), __('ui.mcp_page.access_value'), false],
                        [__('ui.mcp_page.detail_limits'), __('ui.mcp_page.limits_value', ['requests' => \App\Support\Mcp\VatMcpServer::REQUESTS_PER_MINUTE, 'checks' => \App\Support\Mcp\VatMcpServer::VIES_CHECKS_PER_MINUTE]), false],
                        [__('ui.mcp_page.detail_data'), __('ui.mcp_page.data_value'), false],
                    ] as [$label, $value, $mono])
                        <div class="grid gap-1 py-3 sm:grid-cols-[8.5rem_minmax(0,1fr)] sm:gap-4">
                            <dt class="font-display text-[0.8125rem] font-medium text-ink-muted">{{ $label }}</dt>
                            <dd @class(['text-ink', 'font-mono text-xs leading-6 break-all' => $mono])>{{ $value }}</dd>
                        </div>
                    @endforeach
                    <div class="grid gap-1 py-3 sm:grid-cols-[8.5rem_minmax(0,1fr)] sm:gap-4">
                        <dt class="font-display text-[0.8125rem] font-medium text-ink-muted">{{ __('ui.mcp_page.detail_discovery') }}</dt>
                        <dd>
                            <ul class="space-y-1">
                                <li><a href="/.well-known/mcp/server-card.json" class="app-link">{{ __('ui.mcp_page.server_card') }}</a></li>
                                <li><a href="/llms.txt" class="app-link">llms.txt</a></li>
                                <li><a href="/api/v1/openapi.json" class="app-link">{{ __('ui.sitemap.openapi') }}</a></li>
                            </ul>
                        </dd>
                    </div>
                </dl>
            </section>
        </div>

        <section aria-labelledby="mcp-faq" class="max-w-3xl">
            <h2 id="mcp-faq" class="text-2xl font-semibold tracking-[-0.02em] text-ink">{{ __('ui.mcp_page.faq_heading') }}</h2>
            <div class="app-surface mt-5 divide-y divide-line">
                @foreach($faqs as $faq)
                    <details class="group px-5 sm:px-6" @if($loop->first) open @endif>
                        <summary class="flex min-h-14 cursor-pointer list-none items-center justify-between gap-4 py-4 font-semibold text-ink [&::-webkit-details-marker]:hidden">
                            {{ $faq['q'] }}
                            <x-ui.icon name="chevron-down" class="size-4 shrink-0 text-ink-quiet transition-transform group-open:rotate-180" />
                        </summary>
                        <p class="-mt-1 max-w-[72ch] pb-5 text-sm leading-6 text-ink-muted">{{ $faq['a'] }}</p>
                    </details>
                @endforeach
            </div>
        </section>
    </div>
</div>
