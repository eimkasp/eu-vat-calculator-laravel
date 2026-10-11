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

it('seeds the reduced, super-reduced and parking rates in force today', function () {
    $this->seed();

    $rates = Country::query()->whereIn('iso_code', ['AT', 'EE', 'FI', 'GR', 'LT', 'RO', 'SK'])->orderBy('iso_code')->get()
        ->mapWithKeys(fn (Country $country) => [$country->iso_code => [$country->reducedRates(), $country->super_reduced_rate === null ? null : (float) $country->super_reduced_rate]])
        ->all();

    expect($rates)->toBe([
        'AT' => [[10.0, 13.0], 4.9],
        'EE' => [[9.0, 13.0], null],
        'FI' => [[10.0, 13.5], null],
        'GR' => [[6.0, 13.0], 4.0],
        'LT' => [[5.0, 12.0], null],
        'RO' => [[11.0], null],
        'SK' => [[5.0, 19.0], null],
    ]);
});
