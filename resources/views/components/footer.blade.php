@php
    $columns = [
        __('ui.footer.vat_tools') => [
            [__('ui.footer.vat_calculator'), locale_path('/vat-calculator')],
            [__('ui.nav.vat_number_validator'), locale_path('/vat-number-validator')],
            [__('ui.footer.interactive_map'), locale_path('/vat-map')],
            [__('ui.footer.vat_rate_history'), locale_path('/vat-changes')],
            [__('ui.footer.embed_widget'), route('widget.embed')],
            [__('ui.footer.chrome_extension'), locale_path('/chrome-extension')],
        ],
        __('ui.nav.compare') => [
            ...collect(\App\Support\SiteNavigation::comparisons())->take(6)->map(fn (array $comparison) => [$comparison['label'], $comparison['url']])->all(),
            [__('ui.compare_index.all'), locale_path('/compare')],
        ],
        __('ui.footer.resources') => [
            [__('ui.nav.updates'), locale_path('/blog')],
            [__('ui.changelog.nav_label'), locale_path('/changelog')],
            [__('ui.footer.previous_version'), '/v2/'],
            [__('ui.footer.older_version'), '/v1/'],
            [__('ui.footer.sitemap'), locale_path('/sitemap')],
            [__('ui.footer.donate'), locale_path('/donate')],
            [__('ui.footer.privacy'), locale_path('/privacy')],
        ],
        __('ui.footer.developers') => [
            [__('ui.nav.api'), locale_path('/vat-validation-api')],
            [__('ui.footer.vat_rates_api'), '/api/v1/countries'],
            [__('ui.nav.dataset'), locale_path('/datasets/eu-vat-rates')],
            [__('ui.footer.mcp_server'), locale_path('/mcp-server')],
            [__('ui.styleguide.nav_label'), locale_path('/styleguide')],
            [__('ui.brand.nav_label'), locale_path('/brand')],
            [__('ui.footer.llms_data'), '/llms.txt'],
            [__('ui.footer.xml_sitemap'), '/sitemap.xml'],
        ],
        __('ui.footer.partner_tools') => [
            [__('ui.footer.pdf_tools'), 'https://pdfcheck.online/'],
            [__('ui.footer.eu_vies'), 'https://ec.europa.eu/taxation_customs/vies/'],
            [__('ui.footer.eu_vat_guide'), 'https://europa.eu/youreurope/business/taxation/vat/'],
        ],
    ];

    // Four link columns; the partner links sit in the bottom bar.
    $partnerHeading = __('ui.footer.partner_tools');
    $partners = $columns[$partnerHeading];
    unset($columns[$partnerHeading]);
@endphp

<footer class="mobile-nav-safe app-deferred border-t border-line bg-surface-subtle text-ink">
    <div class="app-container py-12 lg:py-16">
        <div class="grid grid-cols-1 gap-10 lg:grid-cols-[minmax(0,1.25fr)_minmax(0,3fr)]">
            <div class="max-w-sm">
                <a href="{{ locale_path('/') }}" class="inline-flex items-center gap-2.5 rounded-control font-display text-lg font-semibold tracking-[-0.02em] text-ink">
                    <x-ui.logo />
                    {{ __('ui.site_name') }}
                </a>
                <p class="mt-4 text-sm leading-6 text-ink-muted">{{ __('ui.footer.description') }}</p>
                <div class="mt-5 flex flex-wrap items-center gap-2">
                    <a href="https://github.com/eimkasp/eu-vat.info" target="_blank" rel="noopener noreferrer" class="app-button-secondary h-10 min-h-10 px-3" aria-label="{{ __('ui.nav.github') }} ({{ __('ui.opens_new_tab') }})">
                        <x-ui.icon name="github" class="size-4" />
                        GitHub
                    </a>
                    <a href="https://chromewebstore.google.com/detail/eu-vat-calculator/fifmbbpgopnifnoginhmjjedjnabdkka" target="_blank" rel="noopener noreferrer" class="app-button-secondary h-10 min-h-10 px-3">
                        <x-ui.icon name="puzzle" class="size-4" />
                        {{ __('ui.footer.chrome_extension') }}
                    <x-ui.new-tab /></a>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-8 sm:grid-cols-4">
                @foreach($columns as $heading => $links)
                    <div>
                        <h2 class="font-display text-sm font-medium tracking-normal text-ink">{{ $heading }}</h2>
                        <ul class="mt-4 space-y-2.5 text-sm hyphens-auto">
                            @foreach($links as [$label, $url])
                                @php($external = str_starts_with($url, 'http') && ! str_starts_with($url, url('/')))
                                <li>
                                    <a href="{{ $url }}" @if($external) target="_blank" rel="noopener noreferrer" @endif class="text-ink-muted underline decoration-transparent underline-offset-4 transition-colors hover:text-ink hover:decoration-action">@if($external){{ Str::beforeLast($label, ' ') === $label ? '' : Str::beforeLast($label, ' ').' ' }}<span class="whitespace-nowrap">{{ Str::afterLast($label, ' ') }}<x-ui.icon name="arrow-up-right" class="ml-1 inline size-3 align-[-1px] text-ink-quiet" /></span>@else{{ $label }}@endif@if($external)<x-ui.new-tab />@endif</a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="mt-12 flex flex-col gap-4 border-t border-line pt-6 text-sm text-ink-muted md:flex-row md:items-center md:justify-between">
            <div class="flex flex-col gap-2">
                <p>&copy; {{ date('Y') }} {{ __('ui.site_name') }}. {{ __('ui.all_rights_reserved') }}</p>
                <nav aria-labelledby="footer-partners" class="flex flex-wrap items-center gap-x-4">
                    <h2 id="footer-partners" class="font-display font-medium text-ink">{{ $partnerHeading }}</h2>
                    @foreach($partners as [$label, $url])
                        <a href="{{ $url }}" target="_blank" rel="noopener noreferrer" class="inline-flex min-h-11 items-center gap-1 underline decoration-transparent underline-offset-4 transition-colors hover:text-ink hover:decoration-action">{{ $label }}<x-ui.icon name="arrow-up-right" class="size-3 text-ink-quiet" /><x-ui.new-tab /></a>
                    @endforeach
                </nav>
            </div>
            <p class="flex items-start gap-2">
                <x-ui.icon name="shield-check" class="mt-0.5 size-4 shrink-0 text-action" />
                {{ __('ui.footer.data_source_note') }}
            </p>
        </div>
    </div>
</footer>
