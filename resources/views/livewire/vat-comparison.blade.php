@use('App\Models\Country')
@use('App\Services\Seo\InternalLinkService')
@use('App\Support\Seo\SeoPolicy')
@use('App\Support\SiteNavigation')
@use('App\Support\Vat\Money')
@use('App\Support\Vat\VatCalculation')

@php
    $canonical = app(SeoPolicy::class)->canonicalHost().locale_path(InternalLinkService::comparisonPath($slugs));
    $three = $countries->count() === 3;
    $names = $three
        ? ['first' => $countries[0]->name, 'second' => $countries[1]->name, 'third' => $countries[2]->name]
        : ['left' => $countries[0]->name, 'right' => $countries[1]->name];
    $title = __($three ? 'ui.compare.title_three' : 'ui.compare.title', $names);
    $rates = $countries->map(fn (Country $country) => (float) $country->standard_rate);
    $highest = $rates->max();
    $lowest = $rates->min();
    $difference = round((float) $countries[0]->standard_rate - (float) $countries[1]->standard_rate, 2);
    $higher = ! $three && $difference !== 0.0 ? ($difference > 0 ? $countries[0] : $countries[1]) : null;
    $rate = fn ($value) => (float) $value > 0 ? Country::formatRate($value).'%' : __('ui.compare.none');
    $rows = [
        [__('ui.compare.reduced_rates'), fn (Country $c) => $c->formattedReducedRates() ?? __('ui.compare.none')],
        [__('ui.compare.super_reduced'), fn (Country $c) => $rate($c->super_reduced_rate)],
        [__('ui.compare.parking'), fn (Country $c) => $rate($c->parking_rate)],
        [__('ui.compare.currency'), fn (Country $c) => $c->currencyCode()],
        [__('ui.compare.vies'), fn (Country $c) => $c->vies_available ? __('ui.compare.available') : __('ui.compare.not_available')],
    ];
    $columns = $three ? 'grid-cols-1 xl:grid-cols-3' : 'grid-cols-1 md:grid-cols-2';
    $hasEu = $countries->contains(fn (Country $country) => $country->is_eu_member);
    $hasOther = $countries->contains(fn (Country $country) => ! $country->is_eu_member);
@endphp

@section('seo')
    <x-seo-meta
        :title="__($three ? 'ui.compare.meta_title_three' : 'ui.compare.meta_title', $names)"
        :description="__($three ? 'ui.compare.meta_description_three' : 'ui.compare.meta_description', $names)"
        :url="$canonical"
        :robots="$indexable ? null : 'noindex, follow'"
    >
        <x-json-ld :data="[
            '@type' => 'WebPage',
            '@id' => $canonical.'#webpage',
            'name' => $title,
            'url' => $canonical,
            'inLanguage' => app()->getLocale(),
            'about' => $countries->map(fn (Country $country) => ['@type' => 'Country', 'name' => $country->name])->all(),
        ]" />
    </x-seo-meta>
@endsection

<div>
    <x-page-header :title="$title" :description="__('ui.compare.intro')" :breadcrumbs="[__('ui.nav.compare') => locale_path('/compare'), $title => '']" />

    <div class="app-container space-y-8 py-8 sm:py-10">
        <x-rate-freshness :updated="$countries->pluck('updated_at')->filter()->max()" :eu="$hasEu" class="mb-4">
            {{-- A mix of EU and other European countries draws on both sources. --}}
            @if($hasEu && $hasOther)
                <a href="{{ config('calculator.dataset_source_url') }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1.5 underline decoration-line-strong decoration-1 underline-offset-[3px] transition-colors hover:text-action hover:decoration-action">
                    <x-ui.icon name="database" class="size-3.5 shrink-0" />
                    {{ __('ui.trust.source_dataset') }}
                    <x-ui.icon name="arrow-up-right" class="size-3 shrink-0" />
                <x-ui.new-tab /></a>
            @endif
        </x-rate-freshness>
        <section class="grid gap-4 {{ $columns }}" aria-label="{{ __('ui.compare.standard_rate') }}">
            @foreach($countries as $country)
                @php($countryRate = (float) $country->standard_rate)
                <div class="app-surface flex items-center gap-5 p-5 sm:p-6">
                    <x-ui.flag :iso="$country->iso_code" size="xl" :lazy="false" />
                    <div class="min-w-0 flex-1">
                        <h2 @class(['text-lg font-semibold text-ink', 'truncate' => ! $three, 'text-balance' => $three])>{{ $country->name }}</h2>
                        <p class="text-sm text-ink-muted">{{ __('ui.compare.standard_rate') }}</p>
                    </div>
                    <div class="text-right">
                        <p class="tabular font-display text-4xl font-semibold tracking-[-0.03em] text-ink">{{ Country::formatRate($country->standard_rate) }}%</p>
                        @if($highest === $lowest)
                            <p class="mt-1 text-xs font-semibold text-ink-muted">{{ __('ui.compare.same_rate') }}</p>
                        @elseif($higher?->is($country))
                            <p class="mt-1 inline-flex items-center gap-1 text-xs font-semibold text-danger">
                                <x-ui.icon name="trending-up" class="size-3.5" />
                                {{ __('ui.compare.higher_by', ['points' => Country::formatRate(abs($difference))]) }}
                            </p>
                        @elseif($three && $countryRate === $highest)
                            <p class="mt-1 inline-flex items-center gap-1 text-xs font-semibold text-danger">
                                <x-ui.icon name="trending-up" class="size-3.5" />
                                {{ __('ui.home_page.stat_highest') }}
                            </p>
                        @elseif($three && $countryRate === $lowest)
                            <p class="mt-1 inline-flex items-center gap-1 text-xs font-semibold text-success">
                                <x-ui.icon name="trending-down" class="size-3.5" />
                                {{ __('ui.home_page.stat_lowest') }}
                            </p>
                        @endif
                    </div>
                </div>
            @endforeach
        </section>

        <section class="app-surface overflow-hidden">
            <h2 id="comparison-table" class="sr-only">{{ $title }}</h2>
            <div class="relative overflow-x-auto" tabindex="0" role="group" aria-labelledby="comparison-table">
                <table @class(['app-table table-fixed', 'min-w-[36rem] sm:min-w-[44rem]' => $three, 'sm:min-w-[36rem]' => ! $three])>
                    <thead>
                        <tr>
                            <th scope="col" @class(['pl-5 sm:pl-6', 'w-28 sm:w-1/4 sticky left-0 z-[1]' => $three, 'w-1/3' => ! $three])>{{ __('ui.compare.measure') }}</th>
                            @foreach($countries as $country)
                                <th scope="col" class="pr-5 sm:pr-6">
                                    <span class="flex items-center gap-2 text-ink"><x-ui.flag :iso="$country->iso_code" />{{ $country->name }}</span>
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <th scope="row" @class(['pl-5 sm:pl-6', 'sticky left-0 z-[1] bg-surface' => $three])>{{ __('ui.compare.standard_rate') }}</th>
                            @foreach($countries as $country)
                                <td class="tabular pr-5 font-display text-lg font-semibold text-ink sm:pr-6">{{ Country::formatRate($country->standard_rate) }}%</td>
                            @endforeach
                        </tr>
                        @foreach($rows as [$label, $value])
                            <tr>
                                <th scope="row" @class(['pl-5 sm:pl-6', 'sticky left-0 z-[1] bg-surface' => $three])>{{ $label }}</th>
                                @foreach($countries as $country)
                                    <td class="tabular pr-5 text-ink sm:pr-6">{{ $value($country) }}</td>
                                @endforeach
                            </tr>
                        @endforeach
                        <tr>
                            <th scope="row" @class(['pl-5 sm:pl-6', 'sticky left-0 z-[1] bg-surface' => $three])>{{ __('ui.compare.changes') }}</th>
                            @foreach($countries as $country)
                                <td class="tabular pr-5 text-ink sm:pr-6">{{ number_format($country->vat_rate_changes_count) }}</td>
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
            <div class="mt-4 grid gap-4 {{ $columns }}">
                @foreach($countries as $country)
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

        <section aria-labelledby="change-comparison" class="app-surface p-5 sm:p-6" data-markdown-skip>
            <h2 id="change-comparison" class="text-lg font-semibold text-ink">{{ __('ui.compare.change_heading') }}</h2>
            <x-compare-picker :selected="$slugs" class="mt-4" />
        </section>

        @php($others = collect(SiteNavigation::comparisons())->reject(fn (array $comparison) => [$comparison['left']->slug, $comparison['right']->slug] === $slugs))
        @if($others->isNotEmpty())
            <section aria-labelledby="more-comparisons">
                <div class="flex flex-wrap items-baseline justify-between gap-2">
                    <h2 id="more-comparisons" class="text-lg font-semibold text-ink">{{ __('ui.compare_index.more') }}</h2>
                    <a href="{{ locale_path('/compare') }}" class="app-link inline-flex min-h-11 items-center text-sm">{{ __('ui.compare_index.all') }}</a>
                </div>
                <ul class="mt-4 grid grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach($others as $comparison)
                        @php($shares = in_array($comparison['left']->slug, $slugs, true) || in_array($comparison['right']->slug, $slugs, true))
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
