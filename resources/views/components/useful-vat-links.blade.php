@php
    $links = [
        ['icon' => 'shield-check', 'title' => __('ui.useful_links.vies_service'), 'text' => __('ui.footer.eu_vies'), 'url' => 'https://ec.europa.eu/taxation_customs/vies/'],
        ['icon' => 'book', 'title' => __('ui.useful_links.your_europe'), 'text' => __('ui.footer.eu_vat_guide'), 'url' => 'https://europa.eu/youreurope/business/taxation/vat/'],
        ['icon' => 'database', 'title' => __('ui.nav.dataset'), 'text' => __('ui.nav.dataset_desc'), 'url' => locale_path('/datasets/eu-vat-rates')],
    ];
@endphp

<section class="app-surface p-5" aria-labelledby="useful-links-heading">
    <h2 id="useful-links-heading" class="text-base font-bold text-ink">{{ __('ui.useful_links.title') }}</h2>
    <ul class="mt-3 space-y-1">
        @foreach($links as $link)
            @php($external = str_starts_with($link['url'], 'http'))
            <li>
                <a href="{{ $link['url'] }}" @if($external) target="_blank" rel="noopener noreferrer" @endif class="group flex items-center gap-3 rounded-control px-2 py-2 transition-colors hover:bg-surface-subtle">
                    <span class="flex size-9 shrink-0 items-center justify-center rounded-control bg-action-soft text-action">
                        <x-ui.icon :name="$link['icon']" class="size-4" />
                    </span>
                    <span class="min-w-0 flex-1">
                        <span class="block truncate text-sm font-semibold text-ink group-hover:text-action">{{ $link['title'] }}</span>
                        <span class="block truncate text-xs text-ink-muted">{{ $link['text'] }}</span>
                    </span>
                    <x-ui.icon :name="$external ? 'arrow-up-right' : 'arrow-right'" class="size-4 text-ink-quiet" />
                @if($external)<x-ui.new-tab />@endif</a>
            </li>
        @endforeach
    </ul>
</section>
