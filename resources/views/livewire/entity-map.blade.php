@php
    $canonical = app(\App\Support\Seo\SeoPolicy::class)->canonicalHost().locale_path('/entity-map');
@endphp

@section('seo')
    <x-seo-meta
        title="EU VAT Info Entity Map: What We Are, Offer and Cite"
        description="One page that defines EU VAT Info, its tools, prices, terms and sources once, each backed by a passage from the site and checked against the live page."
        :url="$canonical">
        <x-json-ld :data="['@graph' => $graph]" />
    </x-seo-meta>
@endsection

<div>
    <x-page-header
        title="EU VAT Info entity map"
        description="Each thing EU VAT Info is or offers, defined once and backed by a passage from this site. Every passage is checked against the page it quotes."
        :breadcrumbs="['Entity map' => '']"
    >
        <x-slot:actions>
            <span class="inline-flex items-center gap-2 rounded-control border border-line bg-surface-subtle px-3 py-1.5 text-sm text-ink-muted">
                <x-ui.icon name="clock" class="size-4" />
                <span>Rates updated <time class="tabular" datetime="{{ $dateModified->toDateString() }}">{{ $dateModified->format('j M Y') }}</time></span>
            </span>
        </x-slot:actions>
    </x-page-header>

    <div class="app-container space-y-8 py-8 sm:py-10">
        @foreach($sections as $section)
            <section class="app-surface overflow-hidden" aria-labelledby="section-{{ \Illuminate\Support\Str::slug($section['title']) }}">
                <h2 id="section-{{ \Illuminate\Support\Str::slug($section['title']) }}" class="border-b border-line px-5 py-4 text-lg font-semibold text-ink sm:px-6">{{ $section['title'] }}</h2>
                <div class="divide-y divide-line">
                    @foreach($section['entities'] as $entity)
                        <article id="{{ $entity['id'] }}" class="scroll-mt-24 px-5 py-5 sm:px-6">
                            <h3 class="text-base font-semibold text-ink">
                                <a class="app-link" href="{{ $entity['url'] }}">{{ $entity['name'] }}</a>
                            </h3>
                            <p class="mt-2 max-w-[68ch] text-base leading-7 text-ink-muted">{{ $entity['definition'] }}</p>
                            @foreach($entity['passages'] as $passage)
                                <blockquote class="mt-3 max-w-[68ch] border-l-2 border-line pl-4 text-sm leading-6 text-ink-muted">
                                    <p>{{ $passage['text'] }}</p>
                                    <footer class="mt-1"><a class="app-link" href="{{ $passage['url'] }}">{{ $passage['url'] }}</a></footer>
                                </blockquote>
                            @endforeach
                        </article>
                    @endforeach
                </div>
            </section>
        @endforeach

        <section class="app-surface overflow-hidden" aria-labelledby="section-member-states">
            <h2 id="section-member-states" class="border-b border-line px-5 py-4 text-lg font-semibold text-ink sm:px-6">Member states</h2>
            <p class="max-w-[68ch] px-5 pt-4 text-base leading-7 text-ink-muted sm:px-6">The {{ count($countries) }} EU member states, each with its standard VAT rate and the page for its calculator. Reduced, super-reduced and parking rates are on each country page.</p>
            <div class="overflow-x-auto px-5 py-4 sm:px-6">
                <table class="w-full min-w-[28rem] text-left text-sm">
                    <thead>
                        <tr class="border-b border-line text-ink-muted">
                            <th scope="col" class="py-2 pr-4 font-medium">Country</th>
                            <th scope="col" class="py-2 pr-4 font-medium">ISO</th>
                            <th scope="col" class="py-2 text-right font-medium">Standard rate</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        @foreach($countries as $country)
                            <tr id="country-{{ $country->slug }}">
                                <th scope="row" class="py-2 pr-4 font-medium text-ink"><a class="app-link" href="/vat-calculator/{{ $country->slug }}">{{ $country->name }}</a></th>
                                <td class="py-2 pr-4 font-mono text-ink-muted">{{ $country->iso_code }}</td>
                                <td class="tabular py-2 text-right text-ink">{{ $country->formatRate($country->standard_rate) }}%</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</div>
