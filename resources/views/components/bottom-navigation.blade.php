@use('App\Support\SiteNavigation')

@php
    $items = [
        ['label' => __('ui.bottom_nav.home'), 'url' => locale_path('/'), 'icon' => 'home', 'active' => SiteNavigation::isActive('home')],
        ['label' => __('ui.bottom_nav.calculator'), 'url' => locale_path('/vat-calculator'), 'icon' => 'calculator', 'active' => SiteNavigation::isActive('vat-calculator*', 'shared-calculation', 'top-calculations*')],
        ['label' => __('ui.bottom_nav.validator'), 'url' => locale_path('/vat-number-validator'), 'icon' => 'shield-check', 'active' => SiteNavigation::isActive('vies-validator*')],
        ['label' => __('ui.bottom_nav.map'), 'url' => locale_path('/vat-map'), 'icon' => 'map', 'active' => SiteNavigation::isActive('vat-map')],
    ];
@endphp

<nav class="fixed inset-x-0 bottom-0 z-30 border-t border-line bg-surface pb-[env(safe-area-inset-bottom)] md:hidden" aria-label="{{ __('ui.nav.shortcuts') }}">
    <div class="mx-auto grid h-16 max-w-lg grid-cols-5">
        @foreach($items as $item)
            <a href="{{ $item['url'] }}" class="app-tab" @if($item['active']) aria-current="page" @endif>
                <x-ui.icon :name="$item['icon']" class="size-5" />
                <span class="max-w-full truncate">{{ $item['label'] }}</span>
            </a>
        @endforeach
        <button type="button" @click="$store.palette.show()" class="app-tab">
            <x-ui.icon name="search" class="size-5" />
            <span class="max-w-full truncate">{{ __('ui.nav.search') }}</span>
        </button>
    </div>
</nav>
