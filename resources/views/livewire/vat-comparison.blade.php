@use('App\Models\Country')
@use('App\Support\Seo\SeoPolicy')
@use('App\Support\Vat\Money')
@use('App\Support\Vat\VatCalculation')

@php
    $canonical = app(SeoPolicy::class)->canonicalHost().locale_path('/compare/'.$leftCountry->slug.'-vs-'.$rightCountry->slug.'-vat');
    $names = ['left' => $leftCountry->name, 'right' => $rightCountry->name];
    $title = __('ui.compare.title', $names);
    $pair = [
        ['country' => $leftCountry, 'changes' => $leftChanges],
        ['country' => $rightCountry, 'changes' => $rightChanges],
    ];
    $difference = round((float) $leftCountry->standard_rate - (float) $rightCountry->standard_rate, 2);
    $higher = $difference > 0 ? $leftCountry : ($difference < 0 ? $rightCountry : null);
    $rate = fn ($value) => (float) $value > 0 ? Country::formatRate($value).'%' : __('ui.compare.none');
    $rows = [
        [__('ui.compare.reduced_rates'), fn (Country $c) => $c->formattedReducedRates() ?? __('ui.compare.none')],
        [__('ui.compare.super_reduced'), fn (Country $c) => $rate($c->super_reduced_rate)],
        [__('ui.compare.parking'), fn (Country $c) => $rate($c->parking_rate)],
        [__('ui.compare.currency'), fn (Country $c) => $c->currencyCode()],
        [__('ui.compare.vies'), fn (Country $c) => $c->vies_available ? __('ui.compare.available') : __('ui.compare.not_available')],
    ];
@endphp

@section('seo')
    <x-seo-meta :title="__('ui.compare.meta_title', $names)" :description="__('ui.compare.meta_description', $names)" :url="$canonical">
        <x-json-ld :data="[
            '@type' => 'WebPage',
            '@id' => $canonical.'#webpage',
            'name' => $title,
            'url' => $canonical,
            'inLanguage' => app()->getLocale(),
            'about' => [
                ['@type' => 'Country', 'name' => $leftCountry->name],
                ['@type' => 'Country', 'name' => $rightCountry->name],
            ],
        ]" />
    </x-seo-meta>
@endsection

<div>
    <x-page-header :title="$title" :description="__('ui.compare.intro')" :breadcrumbs="[__('ui.nav.compare') => locale_path('/compare'), $title => '']" />

    <div class="app-container space-y-8 py-8 sm:py-10">
        <x-rate-freshness :updated="collect([$leftCountry->updated_at, $rightCountry->updated_at])->filter()->max()" class="mb-4" />
        <section class="grid gap-4 md:grid-cols-2" aria-label="{{ __('ui.compare.standard_rate') }}">
            @foreach($pair as ['country' => $country])
                @php($isHigher = $higher?->is($country))
                <div class="app-surface flex items-center gap-5 p-5 sm:p-6">
                    <x-ui.flag :iso="$country->iso_code" size="xl" :lazy="false" />
                    <div class="min-w-0 flex-1">
                        <h2 class="truncate text-lg font-bold text-ink">{{ $country->name }}</h2>
                        <p class="text-sm text-ink-muted">{{ __('ui.compare.standard_rate') }}</p>
                    </div>
                    <div class="text-right">
                        <p class="tabular font-display text-4xl font-semibold tracking-[-0.03em] text-ink">{{ Country::formatRate($country->standard_rate) }}%</p>
                        @if($higher === null)
                            <p class="mt-1 text-xs font-semibold text-ink-muted">{{ __('ui.compare.same_rate') }}</p>
                        @elseif($isHigher)
                            <p class="mt-1 inline-flex items-center gap-1 text-xs font-semibold text-danger">
                                <x-ui.icon name="trending-up" class="size-3.5" />
                                {{ __('ui.compare.higher_by', ['points' => Country::formatRate(abs($difference))]) }}
                            </p>
                        @endif
                    </div>
                </div>
            @endforeach
        </section>

        <section class="app-surface overflow-hidden">
            <h2 id="comparison-table" class="sr-only">{{ $title }}</h2>
            <div class="relative overflow-x-auto" tabindex="0" role="group" aria-labelledby="comparison-table">
                <table class="app-table table-fixed sm:min-w-[36rem]">
                    <thead>
                        <tr>
                            <th scope="col" class="w-1/3 pl-5 sm:pl-6">{{ __('ui.compare.measure') }}</th>
                            @foreach($pair as ['country' => $country])
                                <th scope="col" class="pr-5 sm:pr-6">
                                    <span class="flex items-center gap-2 text-ink"><x-ui.flag :iso="$country->iso_code" />{{ $country->name }}</span>
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <th scope="row" class="pl-5 sm:pl-6">{{ __('ui.compare.standard_rate') }}</th>
                            @foreach($pair as ['country' => $country])
                                <td class="tabular pr-5 text-lg font-bold text-ink sm:pr-6">{{ Country::formatRate($country->standard_rate) }}%</td>
                            @endforeach
                        </tr>
                        @foreach($rows as [$label, $value])
                            <tr>
                                <th scope="row" class="pl-5 sm:pl-6">{{ $label }}</th>
                                @foreach($pair as ['country' => $country])
                                    <td class="tabular pr-5 text-ink sm:pr-6">{{ $value($country) }}</td>
                                @endforeach
                            </tr>
                        @endforeach
                        <tr>
                            <th scope="row" class="pl-5 sm:pl-6">{{ __('ui.compare.changes') }}</th>
                            @foreach($pair as ['country' => $country, 'changes' => $changes])
                                <td class="tabular pr-5 text-ink sm:pr-6">{{ number_format($changes) }}</td>
                            @endforeach
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <section aria-labelledby="comparison-example">
            <div class="flex flex-wrap items-baseline justify-between gap-2">
                <h2 id="comparison-example" class="text-lg font-bold text-ink">{{ __('ui.compare.example_heading') }}</h2>
                <p class="text-sm text-ink-muted">{{ __('ui.compare.example_note') }}</p>
            </div>
            <div class="mt-4 grid gap-4 md:grid-cols-2">
                @foreach($pair as ['country' => $country, 'changes' => $changes])
                    @php($example = VatCalculation::addVat(100, $country->standard_rate))
                    <div class="app-surface p-5 sm:p-6">
                        <div class="flex items-center gap-2.5">
                            <x-ui.flag :iso="$country->iso_code" />
                            <h3 class="font-semibold text-ink">{{ $country->name }}</h3>
                        </div>
                        <dl class="mt-4 grid grid-cols-3 gap-3 text-sm">
                            <div>
                                <dt class="text-xs text-ink-muted">{{ __('ui.calculator.net_short') }}</dt>
                                <dd class="tabular mt-0.5 font-semibold text-ink">{{ Money::format($example->net, $country->currencyCode()) }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs text-ink-muted">{{ __('ui.calculator.vat_short') }}</dt>
                                <dd class="tabular mt-0.5 font-semibold text-action">+{{ Money::format($example->vat, $country->currencyCode()) }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs text-ink-muted">{{ __('ui.calculator.gross_amount') }}</dt>
                                <dd class="tabular mt-0.5 font-bold text-ink">{{ Money::format($example->gross, $country->currencyCode()) }}</dd>
                            </div>
                        </dl>
                        <div class="mt-5 flex flex-wrap gap-2 border-t border-line pt-4">
                            <a href="{{ locale_path('/vat-calculator/'.$country->slug) }}" class="app-button-secondary h-10 min-h-10 px-4">
                                <x-ui.icon name="calculator" class="size-4" />
                                {{ __('ui.compare.calculator') }}
                            </a>
                            @if($country->hasVatHistory())
                                <a href="{{ locale_path('/vat-rates/'.$country->slug.'/history') }}" class="app-button-secondary h-10 min-h-10 px-4">
                                    <x-ui.icon name="history" class="size-4" />
                                    {{ __('ui.compare.history') }}
                                </a>
                            @endif
                            @if($country->vies_available)
                                <a href="{{ locale_path('/vat-number-validator/'.$country->slug) }}" class="app-button-secondary h-10 min-h-10 px-4">
                                    <x-ui.icon name="shield-check" class="size-4" />
                                    {{ __('ui.compare.validator') }}
                                </a>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        @php($others = collect(\App\Support\SiteNavigation::comparisons())->reject(fn (array $comparison) => $comparison['left']->is($leftCountry) && $comparison['right']->is($rightCountry)))
        @if($others->isNotEmpty())
            <section aria-labelledby="more-comparisons">
                <div class="flex flex-wrap items-baseline justify-between gap-2">
                    <h2 id="more-comparisons" class="text-lg font-semibold text-ink">{{ __('ui.compare_index.more') }}</h2>
                    <a href="{{ locale_path('/compare') }}" class="app-link inline-flex min-h-11 items-center text-sm">{{ __('ui.compare_index.all') }}</a>
                </div>
                <ul class="mt-4 grid grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach($others as $comparison)
                        @php($shares = $comparison['left']->is($leftCountry) || $comparison['left']->is($rightCountry) || $comparison['right']->is($leftCountry) || $comparison['right']->is($rightCountry))
                        <li>
                            <a href="{{ $comparison['url'] }}" @class(['pressable flex min-h-12 items-center gap-3 rounded-control border bg-surface px-3.5 py-2.5 hover:border-ink-quiet', 'border-action/40' => $shares, 'border-line' => ! $shares])>
                                <span class="flex shrink-0 -space-x-1.5" aria-hidden="true">
                                    <x-ui.flag :iso="$comparison['left']->iso_code" size="sm" />
                                    <x-ui.flag :iso="$comparison['right']->iso_code" size="sm" />
                                </span>
                                <span class="min-w-0 flex-1 font-display text-sm font-medium text-ink">{{ $comparison['label'] }}</span>
                                <span class="tabular shrink-0 text-xs text-ink-muted">{{ Country::formatRate($comparison['left']->standard_rate) }}% / {{ Country::formatRate($comparison['right']->standard_rate) }}%</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif

        <p class="max-w-[72ch] text-sm leading-6 text-ink-muted">{{ __('ui.compare.disclaimer') }}</p>
    </div>
</div>
