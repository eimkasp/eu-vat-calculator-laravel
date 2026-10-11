<?php

use App\Livewire\ViesValidatorPage;
use App\Models\Country;
use App\Models\VatRateRule;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

it('shows maintained country-specific VAT number guidance with an official source', function () {
    Country::factory()->create([
        'name' => 'Germany',
        'slug' => 'germany',
        'iso_code' => 'DE',
        'is_eu_member' => true,
    ]);

    $this->get('/vat-number-validator/germany')
        ->assertOk()
        ->assertSee('DE999999999')
        ->assertSee('Only national tax administrations issue VAT identification numbers')
        ->assertSee('taxation-customs.ec.europa.eu/taxation/vat/vat-directive/vat-identification-numbers_en');
});

it('uses the EL VAT prefix guidance for Greece', function () {
    Country::factory()->create([
        'name' => 'Greece',
        'slug' => 'greece',
        'iso_code' => 'GR',
        'is_eu_member' => true,
    ]);

    $this->get('/vat-number-validator/greece')
        ->assertOk()
        ->assertSee('EL999999999')
        ->assertSee('Greece uses EL for VAT identification numbers');
});

it('moves a typed EL prefix into the Greek country field before validating', function () {
    Country::factory()->create(['name' => 'Greece', 'slug' => 'greece', 'iso_code' => 'GR']);
    Http::fake(['ec.europa.eu/*' => Http::response(['valid' => true, 'name' => 'ACME', 'address' => 'Athens', 'requestIdentifier' => ''])]);

    Livewire::test(ViesValidatorPage::class)
        ->set('vat_number', 'EL 123-456-789')
        ->call('validateVat')
        ->assertSet('country_code', 'GR')
        ->assertSet('vat_number', '123456789')
        ->assertSet('result.prefix', 'EL');

    Http::assertSent(fn ($request) => $request['countryCode'] === 'EL' && $request['vatNumber'] === '123456789');
});

it('publishes only sourced and verified category VAT rules', function () {
    $country = Country::factory()->create(['is_eu_member' => true]);

    $verified = VatRateRule::create([
        'country_id' => $country->id,
        'category_slug' => 'books',
        'category_name' => 'Books',
        'rate_type' => 'reduced',
        'rate' => 7,
        'source_url' => 'https://example.gov/books-vat',
        'verified_at' => now(),
        'published_at' => now()->subMinute(),
    ]);
    VatRateRule::create([
        'country_id' => $country->id,
        'category_slug' => 'food',
        'category_name' => 'Food',
        'rate_type' => 'reduced',
        'rate' => 7,
    ]);

    expect(VatRateRule::indexable()->pluck('id')->all())->toBe([$verified->id]);
});
