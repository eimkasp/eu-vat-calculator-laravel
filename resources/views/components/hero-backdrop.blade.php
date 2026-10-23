{{-- The hero ground: an ASCII field drawn from the 27 standard rates (rateField in resources/js/app.js). --}}
@props(['opacity' => null])
@php($rates = cache()->remember('hero_field_rates', 3600, fn () => \App\Models\Country::query()->where('is_eu_member', true)->pluck('standard_rate')->map(fn ($rate) => (float) $rate)->values()->all()))

<div class="hero-ground" aria-hidden="true" x-data="rateField(@js($rates))"></div>
