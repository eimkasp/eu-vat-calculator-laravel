<?php

use App\Models\Country;
use Database\Seeders\CountriesTableSeeder;

it('seeds a working site with every EU member state flagged for VIES', function () {
    $this->seed(CountriesTableSeeder::class);

    expect(Country::query()->where('is_eu_member', true)->orderBy('iso_code')->pluck('iso_code')->all())
        ->toBe(collect(Country::EU_MEMBER_CODES)->sort()->values()->all())
        ->and(Country::query()->where('vies_available', true)->count())->toBe(count(Country::EU_MEMBER_CODES))
        ->and(Country::query()->where('iso_code', 'NO')->firstOrFail()->is_eu_member)->toBeFalse();

    $this->get('/vat-calculator/germany')->assertOk();
    $this->getJson(route('vat-dataset.json'))->assertOk()->assertJsonCount(count(Country::EU_MEMBER_CODES), 'data');
});

it('seeds the current standard rate from the rate history', function () {
    $this->seed();

    expect(Country::query()->whereIn('iso_code', ['EE', 'FI', 'RO', 'SK'])->orderBy('iso_code')->pluck('standard_rate', 'iso_code')->map(fn ($rate) => (float) $rate)->all())
        ->toBe(['EE' => 24.0, 'FI' => 25.5, 'RO' => 21.0, 'SK' => 23.0]);
});
