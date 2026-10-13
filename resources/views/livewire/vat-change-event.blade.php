@use('App\Models\Country')

@php
    $country = $change->country;
    $baseUrl = app(\App\Support\Seo\SeoPolicy::class)->canonicalHost();
    $canonical = $baseUrl.locale_path('/vat-changes/'.$country->slug.'/'.$change->rate_type.'/'.$change->change_date->toDateString());
    $typeLabel = str_replace('_', '-', $change->rate_type);
    $delta = (float) $change->new_rate - (float) $change->old_rate;
    $up = $delta > 0;
    $upcoming = $change->change_date->isFuture();
    $fromTo = Country::formatRate($change->old_rate).'% to '.Country::formatRate($change->new_rate).'%';
    $headline = $country->name.' '.$typeLabel.' VAT rate '.($upcoming ? 'changes' : 'changed').' from '.$fromTo;
    $metaTitle = $country->name.' '.$typeLabel.' VAT: '.$fromTo.' on '.$change->change_date->format('j M Y').' | EU VAT Info';
    $verb = $delta == 0 ? ($upcoming ? 'changes' : 'changed') : ($upcoming ? ($up ? 'rises' : 'falls') : ($up ? 'rose' : 'fell'));
    $metaDescription = ($upcoming ? 'From ' : 'On ').$change->change_date->format('j F Y').', '.$country->name.'’s '.$typeLabel.' VAT rate '.$verb.' from '.$fromTo.'. See the source, the full rate history and today’s rate.';
@endphp

@section('seo')
    <x-seo-meta :title="$metaTitle" :description="$metaDescription" :url="$canonical" type="article">
        <script type="application/ld+json">{!! json_encode([
            '@@context' => 'https://schema.org',
            '@graph' => [
                ['@type' => 'Article', '@id' => $canonical.'#article', 'headline' => $headline, 'description' => $metaDescription, 'datePublished' => ($change->created_at ?? $change->updated_at)->toIso8601String(), 'dateModified' => $change->updated_at->toIso8601String(), 'mainEntityOfPage' => $canonical, 'author' => ['@type' => 'Organization', 'name' => 'EU VAT Info'], 'citation' => array_values(array_filter([$change->source_url, $change->official_document]))],
            ],
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
    </x-seo-meta>
@endsection

<article>
    <x-page-header
        :title="$headline"
        :description="$change->editorialDescription()"
        :eyebrow="($upcoming ? 'Takes effect ' : 'Effective ').$change->change_date->format('j F Y')"
        :breadcrumbs="['VAT rate changes' => locale_path('/vat-changes'), $country->name => locale_path('/vat-rates/'.$country->slug.'/history'), $change->change_date->format('j M Y') => '']"
    />

    <div class="app-container py-8 sm:py-10">
        <div class="grid grid-cols-1 items-start gap-8 lg:grid-cols-[minmax(0,1fr)_20rem]">
            <div class="min-w-0 space-y-8">
                <section class="app-surface overflow-hidden" aria-label="Rate change">
                    <div class="grid grid-cols-[minmax(0,1fr)_auto_minmax(0,1fr)] items-center gap-4 p-5 text-center sm:p-8">
                        <div>
                            <p class="text-[0.8125rem] font-semibold text-ink-muted">Previous rate</p>
                            <p class="tabular mt-1 text-3xl font-bold tracking-[-0.02em] text-ink-muted sm:text-4xl">{{ Country::formatRate($change->old_rate) }}%</p>
                        </div>
                        <span class="flex size-10 items-center justify-center rounded-full bg-surface-muted text-ink-quiet" aria-hidden="true">
                            <x-ui.icon name="arrow-right" class="size-5" />
                        </span>
                        <div>
                            <p class="text-[0.8125rem] font-semibold text-ink-muted">New rate</p>
                            <p class="tabular mt-1 text-3xl font-bold tracking-[-0.02em] text-ink sm:text-4xl">{{ Country::formatRate($change->new_rate) }}%</p>
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center justify-center gap-x-6 gap-y-2 border-t border-line bg-surface-subtle px-5 py-3 text-sm">
                        <span class="inline-flex items-center gap-2 text-ink-muted">
                            <x-ui.flag :iso="$country->iso_code" size="sm" />
                            {{ $country->name }} · {{ ucfirst($typeLabel) }} rate
                        </span>
                        @if($delta != 0)
                            <span @class(['tabular inline-flex items-center gap-1 font-semibold', 'text-danger' => $up, 'text-success' => ! $up])>
                                <x-ui.icon :name="$up ? 'trending-up' : 'trending-down'" class="size-4" />
                                {{ $up ? 'Increase of' : 'Decrease of' }} {{ Country::formatRate(abs($delta)) }} percentage {{ abs($delta) == 1 ? 'point' : 'points' }}
                            </span>
                        @endif
                    </div>
                </section>

                @if($change->change_reason)
                    <section class="app-surface p-5 sm:p-6" aria-labelledby="change-reason">
                        <h2 id="change-reason" class="text-lg font-bold text-ink">Reason for the change</h2>
                        <p class="mt-2 max-w-[68ch] text-base leading-7 text-ink-muted">{{ $change->change_reason }}</p>
                    </section>
                @endif

                <section class="app-surface overflow-hidden" aria-labelledby="change-scope">
                    <h2 id="change-scope" class="border-b border-line px-5 py-4 text-lg font-bold text-ink sm:px-6">Dates and scope</h2>
                    <dl class="divide-y divide-line text-sm">
                        <div class="flex justify-between gap-4 px-5 py-3 sm:px-6">
                            <dt class="text-ink-muted">Country</dt>
                            <dd class="font-semibold text-ink">{{ $country->name }}</dd>
                        </div>
                        <div class="flex justify-between gap-4 px-5 py-3 sm:px-6">
                            <dt class="text-ink-muted">Rate type</dt>
                            <dd class="font-semibold text-ink">{{ ucfirst($typeLabel) }}</dd>
                        </div>
                        <div class="flex justify-between gap-4 px-5 py-3 sm:px-6">
                            <dt class="text-ink-muted">Effective date</dt>
                            <dd class="tabular font-semibold text-ink">{{ $change->change_date->format('j F Y') }}</dd>
                        </div>
                        @if($change->announced_date)
                            <div class="flex justify-between gap-4 px-5 py-3 sm:px-6">
                                <dt class="text-ink-muted">Announced</dt>
                                <dd class="tabular font-semibold text-ink">{{ $change->announced_date->format('j F Y') }}</dd>
                            </div>
                        @endif
                    </dl>
                </section>
            </div>

            <aside class="space-y-4 lg:sticky lg:top-24">
                <div class="app-surface space-y-3 p-5">
                    @if($change->source_url)
                        <a class="app-button-primary w-full" href="{{ $change->source_url }}" rel="noopener noreferrer">
                            {{ $change->source ?: 'Open official source' }}
                            <x-ui.icon name="arrow-up-right" class="size-4" />
                        </a>
                    @endif
                    <a class="app-button-secondary w-full" href="{{ locale_path('/vat-rates/'.$country->slug.'/history') }}">
                        <x-ui.icon name="history" class="size-4" />
                        {{ $country->name }} VAT history
                    </a>
                    <a class="app-button-secondary w-full" href="{{ locale_path('/vat-calculator/'.$country->slug) }}">
                        <x-ui.icon name="calculator" class="size-4" />
                        Current VAT calculator
                    </a>
                </div>
            </aside>
        </div>
    </div>
</article>
