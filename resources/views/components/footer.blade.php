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
@endphp

<footer class="on-brand mobile-nav-safe app-deferred bg-brand-deep text-white">
    <div class="app-container py-12 lg:py-16">
        <div class="grid grid-cols-1 gap-10 lg:grid-cols-[minmax(0,1.25fr)_minmax(0,3fr)]">
            <div class="max-w-sm">
                <a href="{{ locale_path('/') }}" class="inline-flex items-center gap-2.5 rounded-control text-lg font-bold tracking-[-0.02em] text-white">
                    <x-ui.logo />
                    {{ __('ui.site_name') }}
                </a>
                <p class="mt-4 text-sm leading-6 text-white/70">{{ __('ui.footer.description') }}</p>
                <div class="mt-5 flex flex-wrap items-center gap-2">
                    <a href="https://github.com/eimkasp/eu-vat.info" target="_blank" rel="noopener noreferrer" class="app-button-on-brand h-10 min-h-10 px-3" aria-label="{{ __('ui.nav.github') }} ({{ __('ui.opens_new_tab') }})">
                        <x-ui.icon name="github" class="size-4" />
                        GitHub
                    </a>
                    <a href="https://chromewebstore.google.com/detail/eu-vat-calculator/fifmbbpgopnifnoginhmjjedjnabdkka" target="_blank" rel="noopener noreferrer" class="app-button-on-brand h-10 min-h-10 px-3">
                        <x-ui.icon name="puzzle" class="size-4" />
                        {{ __('ui.footer.chrome_extension') }}
                    <x-ui.new-tab /></a>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-8 sm:grid-cols-4">
                @foreach($columns as $heading => $links)
                    <div>
                        <h2 class="text-xs font-semibold tracking-[0.08em] text-white uppercase">{{ $heading }}</h2>
                        <ul class="mt-4 space-y-2.5 text-sm hyphens-auto wrap-anywhere">
                            @foreach($links as [$label, $url])
                                @php($external = str_starts_with($url, 'http') && ! str_starts_with($url, url('/')))
                                <li>
                                    <a href="{{ $url }}" @if($external) target="_blank" rel="noopener noreferrer" @endif class="text-white/70 transition-colors hover:text-white">@if($external){{ Str::beforeLast($label, ' ') === $label ? '' : Str::beforeLast($label, ' ').' ' }}<span class="whitespace-nowrap">{{ Str::afterLast($label, ' ') }}<x-ui.icon name="arrow-up-right" class="ml-1 inline size-3 align-[-1px] text-white/45" /></span>@else{{ $label }}@endif@if($external)<x-ui.new-tab />@endif</a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="mt-12 flex flex-col gap-4 border-t border-white/10 pt-6 text-sm text-white/60 md:flex-row md:items-center md:justify-between">
            <p class="flex flex-wrap items-center gap-x-3 gap-y-1">
                <span>&copy; {{ date('Y') }} {{ __('ui.site_name') }}. {{ __('ui.all_rights_reserved') }}</span>
                <span class="hidden text-white/40 sm:inline" aria-hidden="true">·</span>
                <a href="https://pdfcheck.online/" target="_blank" rel="noopener noreferrer" class="transition-colors hover:text-white">{{ __('ui.footer.pdf_tools') }}<x-ui.new-tab /></a>
            </p>
            <p class="flex items-start gap-2">
                <x-ui.icon name="shield-check" class="mt-0.5 size-4 shrink-0 text-gold" />
                {{ __('ui.footer.data_source_note') }}
            </p>
        </div>
    </div>
</footer>
