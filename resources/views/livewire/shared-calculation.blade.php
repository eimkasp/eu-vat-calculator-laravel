@use('App\Livewire\SharedCalculation')
@use('App\Models\Country')
@use('App\Support\Seo\SeoPolicy')
@use('App\Support\Vat\Money')
@use('App\Support\Vat\VatCalculation')

@php
    $currency = $countryModel->currencyCode();
    $money = fn (float $value, ?string $code = null) => Money::format($value, $code ?? $currency);
    $adding = $mode === 'exclude';
    $rateText = Money::percent($rate);
    $inputText = $money($calculation->input());
    $headline = __($adding ? 'ui.shared_calc.heading_add' : 'ui.shared_calc.heading_remove', ['amount' => $inputText, 'rate' => Country::formatRate($rate), 'country' => $countryModel->name]);
    $shareUrl = url(SharedCalculation::calculationUrl($country, $amount, $rate, $mode));
    $calculatorUrl = locale_path('/vat-calculator/'.$countryModel->slug).'?'.http_build_query(['amount' => SharedCalculation::segment($amount), 'rate' => SharedCalculation::segment($rate), 'mode' => $mode]);
    $netShare = $calculation->gross > 0 ? round($calculation->net / $calculation->gross * 100, 1) : 100;
    $calculatorAvailable = $countryModel->isCalculatorAvailable();
@endphp

@section('seo')
    <x-seo-meta
        :title="__('ui.shared_calc.meta_title', ['mode' => __($adding ? 'ui.shared_calc.meta_add' : 'ui.shared_calc.meta_remove'), 'rate' => Country::formatRate($rate), 'amount' => $inputText, 'country' => $countryModel->name])"
        :description="__('ui.shared_calc.meta_description', ['country' => $countryModel->name, 'mode' => mb_strtolower(__($adding ? 'ui.shared_calc.meta_add' : 'ui.shared_calc.meta_remove')), 'rate' => Country::formatRate($rate), 'amount' => $inputText, 'net' => $money($calculation->net), 'vat' => $money($calculation->vat), 'gross' => $money($calculation->gross)])"
        :url="$this->indexable ? $shareUrl : app(SeoPolicy::class)->localizedUrl('/vat-calculator/'.$countryModel->slug, config('translation.default_language', 'en'))"
        :robots="$this->indexable ? null : 'noindex, follow'"
        type="website"
    >
        <x-json-ld :data="[
            '@type' => 'WebPage',
            'name' => $headline,
            'url' => $shareUrl,
            'inLanguage' => app()->getLocale(),
            'dateModified' => $countryModel->updated_at?->toIso8601String(),
            'isPartOf' => ['@type' => 'WebSite', 'name' => __('ui.site_name'), 'url' => url(locale_path('/'))],
            'about' => ['@type' => 'Country', 'name' => $countryModel->name],
            'mainEntity' => [
                '@type' => 'MonetaryAmount',
                'name' => __('ui.calculator.total_incl_vat'),
                'currency' => $currency,
                'value' => round($calculation->gross, 2),
            ],
        ]" />
    </x-seo-meta>
@endsection

<div>
    <section class="hero-field">
        <x-hero-backdrop />
        <div class="app-container relative pb-10 pt-6 sm:pb-14 sm:pt-8">
            <x-site-breadcrumbs :items="array_filter([
                __('ui.calculator.breadcrumb_label') => locale_path('/vat-calculator'),
                $countryModel->name => $calculatorAvailable ? locale_path('/vat-calculator/'.$countryModel->slug) : '',
                __('ui.shared_calc.breadcrumb', ['rate' => Country::formatRate($rate), 'amount' => $inputText]) => '',
            ], fn ($url, $label) => $label !== '', ARRAY_FILTER_USE_BOTH)" />

            <div class="mb-7 mt-6 flex max-w-3xl items-center gap-4 sm:mb-9">
                <x-ui.flag :iso="$countryModel->iso_code" size="xl" :lazy="false" class="shrink-0" />
                <h1 class="text-3xl font-semibold tracking-[-0.03em] text-ink sm:text-5xl sm:leading-[1.04]">{{ $headline }}</h1>
            </div>

            <div class="app-surface-raised grid max-w-4xl grid-cols-1 overflow-hidden text-ink md:grid-cols-[minmax(0,1fr)_minmax(0,1.1fr)]">
                <div class="flex flex-col gap-4 p-5 sm:p-7">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="app-badge bg-action-soft text-action-deep">
                            <x-ui.icon :name="$adding ? 'plus' : 'minus'" class="size-3.5" />
                            {{ $adding ? __('ui.shared_calc.vat_added') : __('ui.shared_calc.vat_extracted') }}
                        </span>
                        <span class="app-badge bg-surface-muted text-ink-muted">{{ $this->rateType['label'] }}</span>
                        <span class="app-badge tabular bg-surface-muted text-ink-muted">{{ $rateText }}</span>
                        <span class="app-badge bg-surface-muted text-ink-muted">{{ $currency }}</span>
                    </div>

                    <div>
                        <p class="font-display text-[0.8125rem] font-medium text-ink-muted">{{ $adding ? __('ui.shared_calc.total_incl_vat') : __('ui.shared_calc.net_excl_vat') }}</p>
                        <p class="tabular mt-1 font-display text-4xl font-semibold tracking-[-0.035em] text-ink sm:text-5xl">{{ $money($adding ? $calculation->gross : $calculation->net) }}</p>
                    </div>

                    <p class="app-note">
                        <x-ui.icon name="info" class="mt-1 size-4 text-action" />
                        {{ __('ui.rate_type_desc.'.$this->rateType['type']) }}
                    </p>

                    <div class="mt-2 flex flex-wrap gap-2.5">
                        @if($calculatorAvailable)
                            <a href="{{ $calculatorUrl }}" class="app-button-primary flex-1 whitespace-nowrap">
                                <x-ui.icon name="calculator" class="size-4" />
                                {{ __('ui.shared_calc.open_in_calculator') }}
                            </a>
                        @endif
                        <button type="button" x-data x-on:click="$copy(@js($shareUrl), @js(__('ui.shared_calc.link_copied')))" class="app-button-secondary flex-1 whitespace-nowrap">
                            <x-ui.icon name="link" class="size-4" />
                            {{ __('ui.shared_calc.copy_share_link') }}
                        </button>
                    </div>
                </div>

                <div class="border-t border-line bg-surface-subtle p-5 sm:p-7 md:border-l md:border-t-0">
                    <dl class="divide-y divide-line overflow-hidden rounded-control border border-line bg-surface text-sm">
                        <div class="flex items-center justify-between gap-4 px-4 py-3">
                            <dt class="text-ink-muted">{{ __('ui.shared_calc.net_excl_label') }}</dt>
                            <dd class="tabular font-semibold text-ink">{{ $money($calculation->net) }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-4 px-4 py-3">
                            <dt class="text-ink-muted">{{ __('ui.shared_calc.vat_percent', ['rate' => Country::formatRate($rate)]) }}</dt>
                            <dd class="tabular font-semibold text-action">{{ $adding ? '+' : '' }}{{ $money($calculation->vat) }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-4 px-4 py-3">
                            <dt class="font-semibold text-ink">{{ __('ui.shared_calc.total_incl') }}</dt>
                            <dd class="tabular font-semibold text-ink">{{ $money($calculation->gross) }}</dd>
                        </div>
                    </dl>

                    <div class="mt-4" aria-hidden="true">
                        <div class="flex h-1.5 gap-0.5 overflow-hidden rounded-xs">
                            <span class="bg-action" style="width: {{ $netShare }}%"></span>
                            <span class="flex-1 bg-action/25"></span>
                        </div>
                        <div class="mt-1.5 flex justify-between text-xs text-ink-muted">
                            <span>{{ __('ui.calculator.net_short') }} {{ Money::percent($netShare) }}</span>
                            <span>{{ __('ui.calculator.vat_short') }} {{ Money::percent(100 - $netShare) }}</span>
                        </div>
                    </div>

                    <div class="mt-5 rounded-control border border-line bg-surface p-4">
                        <h2 class="text-sm font-semibold text-ink">{{ __('ui.shared_calc.formula_heading') }}</h2>
                        <div class="mt-2 space-y-1.5 font-mono text-xs leading-5 text-ink-muted">
                            @if($adding)
                                <p>{{ __('ui.shared_calc.formula_vat_label') }}</p>
                                <p class="text-ink">{{ $money($calculation->net) }} × {{ $rateText }} = {{ $money($calculation->vat) }}</p>
                                <p class="pt-1.5">{{ __('ui.shared_calc.formula_total_label') }}</p>
                                <p class="text-ink">{{ $money($calculation->net) }} + {{ $money($calculation->vat) }} = <strong>{{ $money($calculation->gross) }}</strong></p>
                            @else
                                <p>{{ __('ui.shared_calc.formula_net_label') }}</p>
                                <p class="text-ink">{{ $money($calculation->gross) }} ÷ {{ rtrim(rtrim(number_format(1 + $rate / 100, 4, '.', ''), '0'), '.') }} = <strong>{{ $money($calculation->net) }}</strong></p>
                                <p class="pt-1.5">{{ __('ui.shared_calc.formula_vat_extract_label') }}</p>
                                <p class="text-ink">{{ $money($calculation->gross) }} − {{ $money($calculation->net) }} = {{ $money($calculation->vat) }}</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            <x-rate-freshness :updated="$countryModel->updated_at" :eu="$countryModel->is_eu_member" class="mt-4 max-w-4xl" />
        </div>
    </section>

    <div class="app-container py-10 sm:py-14">
        <div class="grid grid-cols-1 items-start gap-8 lg:grid-cols-[minmax(0,1fr)_20rem]">
            <div class="min-w-0 space-y-8">
                <section class="app-surface overflow-hidden" aria-labelledby="other-amounts">
                    <h2 id="other-amounts" class="border-b border-line px-5 py-4 text-lg font-semibold text-ink sm:px-6">{{ __('ui.shared_calc.same_country_other_amounts', ['country' => $countryModel->name]) }}</h2>
                    <div class="relative overflow-x-auto" tabindex="0" role="group" aria-labelledby="other-amounts">
                        <table class="app-table sm:min-w-[28rem]">
                            <thead>
                                <tr>
                                    <th scope="col" class="pl-5 sm:pl-6">{{ $adding ? __('ui.shared_calc.net_amount') : __('ui.shared_calc.gross_amount') }}</th>
                                    <th scope="col" class="text-right">{{ __('ui.shared_calc.vat_percent', ['rate' => Country::formatRate($rate)]) }}</th>
                                    <th scope="col" class="pr-5 text-right sm:pr-6">{{ $adding ? __('ui.shared_calc.total_incl_vat') : __('ui.shared_calc.net_excl_vat') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach(SharedCalculation::AMOUNTS as $option)
                                    @continue(abs($option - $amount) < 0.001)
                                    @php($row = VatCalculation::make($option, $rate, $mode))
                                    <tr class="transition-colors hover:bg-surface-subtle">
                                        <td class="pl-5 sm:pl-6">
                                            <a href="{{ SharedCalculation::calculationUrl($country, $option, $rate, $mode) }}" class="tabular font-semibold text-ink hover:text-action">{{ $money($row->input()) }}</a>
                                        </td>
                                        <td class="tabular text-right text-ink-muted">{{ $money($row->vat) }}</td>
                                        <td class="tabular pr-5 text-right font-semibold text-ink sm:pr-6">{{ $money($adding ? $row->gross : $row->net) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </section>

                @if($this->nearbyCountries->isNotEmpty())
                    <section class="app-surface overflow-hidden" aria-labelledby="nearby-rates">
                        <div class="border-b border-line px-5 py-4 sm:px-6">
                            <h2 id="nearby-rates" class="text-lg font-semibold text-ink">{{ __('ui.shared_calc.nearby_heading') }}</h2>
                            <p class="mt-1 text-sm text-ink-muted">{{ __('ui.shared_calc.nearby_note') }}</p>
                        </div>
                        <div class="relative overflow-x-auto" tabindex="0" role="group" aria-labelledby="nearby-rates">
                            <table class="app-table min-w-[32rem]">
                                <thead>
                                    <tr>
                                        <th scope="col" class="pl-5 sm:pl-6">{{ __('ui.shared_calc.country') }}</th>
                                        <th scope="col" class="text-right">{{ __('ui.shared_calc.vat_rate') }}</th>
                                        <th scope="col" class="text-right">{{ __('ui.shared_calc.vat_amount') }}</th>
                                        <th scope="col" class="pr-5 text-right sm:pr-6">{{ $adding ? __('ui.shared_calc.total_incl_vat') : __('ui.shared_calc.net_excl_vat') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($this->nearbyCountries as $other)
                                        @php($row = VatCalculation::make($amount, $other->standard_rate, $mode))
                                        <tr class="transition-colors hover:bg-surface-subtle">
                                            <td class="pl-5 sm:pl-6">
                                                <a href="{{ SharedCalculation::calculationUrl($other->slug, $amount, $other->standard_rate, $mode) }}" class="flex items-center gap-2.5 font-semibold text-ink hover:text-action">
                                                    <x-ui.flag :iso="$other->iso_code" />
                                                    {{ $other->name }}
                                                </a>
                                            </td>
                                            <td class="tabular text-right text-ink-muted">{{ Country::formatRate($other->standard_rate) }}%</td>
                                            <td class="tabular text-right text-ink-muted">{{ $money($row->vat, $other->currencyCode()) }}</td>
                                            <td class="tabular pr-5 text-right font-semibold text-ink sm:pr-6">{{ $money($adding ? $row->gross : $row->net, $other->currencyCode()) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </section>
                @endif

                <p class="max-w-[72ch] text-xs leading-5 text-ink-muted">
                    {{ __('ui.shared_calc.disclaimer') }}
                    {{ __('ui.shared_calc.calculated_on', ['date' => now()->locale(app()->getLocale())->isoFormat('LL')]) }}
                </p>
            </div>

            <aside class="space-y-6">
                @if(count($this->rateOptions) > 1)
                    <nav class="app-surface p-5" aria-labelledby="country-rates">
                        <h2 id="country-rates" class="text-base font-semibold text-ink">{{ __('ui.shared_calc.all_rates_title', ['country' => $countryModel->name]) }}</h2>
                        <ul class="mt-3 space-y-1.5">
                            @foreach($this->rateOptions as $option)
                                @php($current = abs($option['rate'] - $rate) < 0.001)
                                <li>
                                    <a href="{{ SharedCalculation::calculationUrl($country, $amount, $option['rate'], $mode) }}" @if($current) aria-current="page" @endif @class(['flex min-h-11 items-center justify-between rounded-control border px-3.5 text-sm font-semibold transition-colors', 'border-action/40 bg-action-soft text-action-deep' => $current, 'border-line text-ink hover:border-line-strong hover:bg-surface-subtle' => ! $current])>
                                        <span>{{ $option['label'] }}</span>
                                        <span class="tabular">{{ Country::formatRate($option['rate']) }}%</span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </nav>
                @endif

                <nav class="app-surface p-5" aria-labelledby="switch-mode">
                    <h2 id="switch-mode" class="text-base font-semibold text-ink">{{ __('ui.shared_calc.switch_mode') }}</h2>
                    <ul class="mt-3 space-y-1.5">
                        @foreach(['exclude' => ['plus', __('ui.shared_calc.add_vat_to_amount')], 'include' => ['minus', __('ui.shared_calc.remove_vat_from_amount')]] as $value => [$icon, $label])
                            @php($current = $mode === $value)
                            <li>
                                <a href="{{ SharedCalculation::calculationUrl($country, $amount, $rate, $value) }}" @if($current) aria-current="page" @endif @class(['flex min-h-11 items-center gap-2.5 rounded-control border px-3.5 text-sm font-semibold transition-colors', 'border-action/40 bg-action-soft text-action-deep' => $current, 'border-line text-ink hover:border-line-strong hover:bg-surface-subtle' => ! $current])>
                                    <x-ui.icon :name="$icon" class="size-4" />
                                    {{ $label }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </nav>

                <a href="{{ locale_path('/top-vat-calculations') }}" class="group app-surface flex items-start gap-3 p-5 transition-colors hover:border-action/40">
                    <span class="flex size-9 shrink-0 items-center justify-center rounded-control bg-action-soft text-action" aria-hidden="true"><x-ui.icon name="trending-up" class="size-4" /></span>
                    <span class="min-w-0">
                        <span class="flex items-center gap-1.5 text-sm font-semibold text-ink group-hover:text-action">
                            {{ __('ui.shared_calc.popular_calculations') }}
                            <x-ui.icon name="arrow-right" class="size-3.5" />
                        </span>
                        <span class="mt-0.5 block text-sm leading-6 text-ink-muted">{{ __('ui.shared_calc.popular_calculations_desc') }}</span>
                    </span>
                </a>
            </aside>
        </div>
    </div>
</div>
