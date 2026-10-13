@use('App\Models\Country')

@php
    $baseUrl = app(\App\Support\Seo\SeoPolicy::class)->canonicalHost();
    $canonical = $baseUrl.locale_path('/vat-rates/'.$country->slug.'/history');
    $typeLabel = fn (string $type) => ucfirst(str_replace('_', '-', $type));
@endphp

@section('seo')
    <x-seo-meta
        :title="$country->name.' VAT Rate History — Changes and Effective Dates'"
        :description="'Review '.$country->name.' VAT rate history, including effective dates, previous rates, current rates and source-backed tax changes.'"
        :url="$canonical">
        <script type="application/ld+json">{!! json_encode([
            '@@context' => 'https://schema.org',
            '@type' => 'Dataset',
            '@id' => $canonical.'#dataset',
            'name' => $country->name.' VAT rate history',
            'description' => 'Historical VAT rates and recorded VAT rate changes for '.$country->name.'.',
            'url' => $canonical,
            'dateModified' => optional($changes->max('updated_at') ?? $rates->max('updated_at') ?? $country->updated_at)->toIso8601String(),
            'license' => 'https://creativecommons.org/licenses/by/4.0/',
            'isBasedOn' => $changes->pluck('source_url')->filter()->unique()->values()->all(),
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
    </x-seo-meta>
@endsection

<div>
    <x-page-header
        :title="$country->name.' VAT rate history'"
        :description="'Every recorded '.$country->name.' VAT rate, with effective periods and source-backed changes.'"
        eyebrow="Historical VAT data"
        :breadcrumbs="[__('ui.nav.vat_history') => locale_path('/vat-changes'), $country->name => locale_path('/vat-calculator/'.$country->slug), 'Rate history' => '']"
    />

    <div class="app-container py-8 sm:py-10">
        <div class="grid grid-cols-1 items-start gap-8 lg:grid-cols-[minmax(0,1fr)_20rem]">
            <div class="min-w-0 space-y-8">
                <section class="app-surface flex flex-wrap items-center gap-x-8 gap-y-4 p-5 sm:p-6" aria-label="Current rate">
                    <div class="flex items-center gap-4">
                        <x-ui.flag :iso="$country->iso_code" size="xl" :lazy="false" />
                        <div>
                            <p class="text-[0.8125rem] font-semibold text-ink-muted">Current standard rate</p>
                            <p class="tabular text-3xl font-bold tracking-[-0.02em] text-ink">{{ Country::formatRate($country->standard_rate) }}%</p>
                        </div>
                    </div>
                    <dl class="flex flex-wrap gap-x-8 gap-y-3 text-sm">
                        <div>
                            <dt class="text-ink-muted">Recorded changes</dt>
                            <dd class="tabular font-semibold text-ink">{{ $changes->count() }}</dd>
                        </div>
                        <div>
                            <dt class="text-ink-muted">Rate periods</dt>
                            <dd class="tabular font-semibold text-ink">{{ $rates->count() }}</dd>
                        </div>
                        @if($rates->isNotEmpty())
                            <div>
                                <dt class="text-ink-muted">Earliest record</dt>
                                <dd class="tabular font-semibold text-ink">{{ $rates->min('effective_from')->format('Y') }}</dd>
                            </div>
                        @endif
                    </dl>
                </section>

                <section class="app-surface overflow-hidden" aria-labelledby="recorded-changes">
                    <h2 id="recorded-changes" class="border-b border-line px-5 py-4 text-lg font-bold text-ink sm:px-6">Recorded changes</h2>
                    @if($changes->isEmpty())
                        <p class="px-5 py-8 text-sm text-ink-muted sm:px-6">No individual rate-change events have been recorded yet.</p>
                    @else
                        <ol class="divide-y divide-line">
                            @foreach($changes as $change)
                                @php
                                    $delta = (float) $change->new_rate - (float) $change->old_rate;
                                    $up = $delta > 0;
                                @endphp
                                <li class="flex flex-col gap-3 px-5 py-4 sm:flex-row sm:items-start sm:gap-6 sm:px-6">
                                    <time class="tabular shrink-0 text-sm text-ink-muted sm:w-24 sm:pt-0.5" datetime="{{ $change->change_date->toDateString() }}">{{ $change->change_date->format('j M Y') }}</time>
                                    <div class="min-w-0 flex-1">
                                        <p class="flex flex-wrap items-center gap-x-3 gap-y-1.5">
                                            <span class="app-badge bg-surface-muted text-ink-muted">{{ $typeLabel($change->rate_type) }}</span>
                                            <span class="tabular text-sm text-ink-muted">
                                                {{ Country::formatRate($change->old_rate) }}%
                                                <x-ui.icon name="arrow-right" class="inline size-3.5 align-[-2px] text-ink-quiet" />
                                                <span class="font-bold text-ink">{{ Country::formatRate($change->new_rate) }}%</span>
                                            </span>
                                            @if($delta != 0)
                                                <span @class(['tabular inline-flex items-center gap-0.5 text-xs font-semibold', 'text-danger' => $up, 'text-success' => ! $up])>
                                                    <x-ui.icon :name="$up ? 'trending-up' : 'trending-down'" class="size-3.5" />
                                                    <span class="sr-only">{{ $up ? 'Increase' : 'Decrease' }}</span>
                                                    {{ $up ? '+' : '−' }}{{ Country::formatRate(abs($delta)) }} pp
                                                </span>
                                            @endif
                                        </p>
                                        @if($change->editorialDescription())
                                            <p class="mt-1.5 text-sm leading-6 text-ink-muted">{{ $change->editorialDescription() }}</p>
                                        @endif
                                        <p class="mt-2 flex flex-wrap gap-x-5 gap-y-1 text-sm">
                                            <a class="inline-flex items-center gap-1 font-semibold text-action hover:text-action-deep hover:underline" href="{{ locale_path('/vat-changes/'.$country->slug.'/'.$change->rate_type.'/'.$change->change_date->toDateString()) }}">
                                                View change details
                                                <x-ui.icon name="chevron-right" class="size-3.5" />
                                            </a>
                                            @if($change->source_url)
                                                <a class="inline-flex items-center gap-1 text-ink-muted hover:text-action hover:underline" href="{{ $change->source_url }}" rel="noopener noreferrer">
                                                    Official source
                                                    <x-ui.icon name="arrow-up-right" class="size-3.5" />
                                                </a>
                                            @endif
                                        </p>
                                    </div>
                                </li>
                            @endforeach
                        </ol>
                    @endif
                </section>

                <section class="app-surface overflow-hidden" aria-labelledby="rate-periods">
                    <h2 id="rate-periods" class="border-b border-line px-5 py-4 text-lg font-bold text-ink sm:px-6">Rate periods</h2>
                    <div class="relative overflow-x-auto" tabindex="0" role="group" aria-labelledby="rate-periods">
                        <table class="app-table min-w-[32rem]">
                            <thead>
                                <tr>
                                    <th scope="col" class="pl-5 sm:pl-6">Rate type</th>
                                    <th scope="col" class="text-right">Rate</th>
                                    <th scope="col">Effective from</th>
                                    <th scope="col" class="pr-5 sm:pr-6">Effective to</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($rates as $rate)
                                    <tr>
                                        <td class="pl-5 font-semibold text-ink sm:pl-6">{{ $typeLabel($rate->type) }}</td>
                                        <td class="tabular text-right font-bold text-ink">{{ Country::formatRate($rate->rate) }}%</td>
                                        <td class="tabular text-ink-muted">{{ $rate->effective_from->format('j M Y') }}</td>
                                        <td class="tabular pr-5 sm:pr-6">
                                            @if($rate->effective_to)
                                                <span class="text-ink-muted">{{ $rate->effective_to->format('j M Y') }}</span>
                                            @else
                                                <span class="app-badge bg-success-soft text-success">Current</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="px-5 py-8 text-center text-ink-muted">No historical rate periods are available.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>

            <aside class="space-y-4 lg:sticky lg:top-24">
                <div class="app-surface space-y-3 p-5">
                    <a class="app-button-primary w-full" href="{{ locale_path('/vat-calculator/'.$country->slug) }}">
                        <x-ui.icon name="calculator" class="size-4" />
                        Calculate {{ $country->name }} VAT
                    </a>
                    @if($country->is_eu_member && $country->vies_available)
                        <a class="app-button-secondary w-full" href="{{ locale_path('/vat-number-validator/'.$country->slug) }}">
                            <x-ui.icon name="shield-check" class="size-4" />
                            Validate a VAT number
                        </a>
                    @endif
                    <a class="app-button-ghost w-full" href="{{ locale_path('/vat-changes') }}">
                        <x-ui.icon name="history" class="size-4" />
                        All VAT rate changes
                    </a>
                </div>
                <p class="app-note">
                    <x-ui.icon name="info" class="mt-1 size-4 text-action" />
                    <span>Effective dates and sources are shown where available. Check the linked authority before making compliance decisions.</span>
                </p>
            </aside>
        </div>
    </div>
</div>
