<?php

use App\Models\Country;
use Carbon\CarbonImmutable;

it('publishes a canonical source-backed EU VAT dataset landing page', function () {
    $country = Country::factory()->create([
        'name' => 'Germany',
        'slug' => 'germany',
        'iso_code' => 'DE',
        'standard_rate' => 19,
        'is_eu_member' => true,
    ]);
    $country->timestamps = false;
    $country->forceFill(['updated_at' => CarbonImmutable::parse('2026-06-12')])->saveQuietly();

    $this->get('/datasets/eu-vat-rates')
        ->assertOk()
        ->assertSee('<link rel="canonical" href="https://eu-vat.info/datasets/eu-vat-rates">', false)
        ->assertSee('"@type":"Dataset"', false)
        ->assertSee('"dateModified":"2026-06-12', false)
        ->assertSee('"isBasedOn"', false)
        ->assertSee('creativecommons.org/licenses/by/4.0')
        ->assertSee('https://eu-vat.info/datasets/eu-vat-rates.csv')
        ->assertSee('https://eu-vat.info/datasets/eu-vat-rates.json')
        ->assertSee('https://eu-vat.info/llms-full.txt');
});

it('serves an EU-only JSON dataset distribution with ISO codes', function () {
    Country::factory()->create([
        'name' => 'Germany',
        'slug' => 'germany',
        'iso_code' => 'DE',
        'is_eu_member' => true,
    ]);
    Country::factory()->create([
        'name' => 'Switzerland',
        'slug' => 'switzerland',
        'iso_code' => 'CH',
        'is_eu_member' => false,
    ]);

    $this->getJson('/datasets/eu-vat-rates.json')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Germany')
        ->assertJsonPath('data.0.iso_code', 'DE')
        ->assertJsonMissing(['name' => 'Switzerland']);
});

it('streams an EU-only CSV distribution', function () {
    Country::factory()->create([
        'name' => 'Germany',
        'slug' => 'germany',
        'iso_code' => 'DE',
        'standard_rate' => 19,
        'is_eu_member' => true,
    ]);
    Country::factory()->create([
        'name' => 'Switzerland',
        'slug' => 'switzerland',
        'iso_code' => 'CH',
        'standard_rate' => 8.1,
        'is_eu_member' => false,
    ]);

    $response = $this->get('/datasets/eu-vat-rates.csv');

    $response
        ->assertOk()
        ->assertDownload('eu-vat-rates.csv');

    $content = $response->streamedContent();
    expect($content)
        ->toContain('country,iso_code,standard_rate,reduced_rate,super_reduced_rate,parking_rate,currency_code,last_updated,reduced_rates')
        ->toContain('Germany,DE,19')
        ->not->toContain('Switzerland');
});
