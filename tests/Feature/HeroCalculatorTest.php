<?php

use App\Livewire\HeroCalculator;
use App\Models\Country;
use Illuminate\Support\Facades\Cache;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;

beforeEach(function () {
    Cache::flush();

    Country::factory()->withRates(23, 8)->create(['name' => 'Poland', 'slug' => 'poland', 'iso_code' => 'PL', 'currency_code' => 'PLN']);
    Country::factory()->withRates(19, 7)->create(['name' => 'Germany', 'slug' => 'germany', 'iso_code' => 'DE', 'currency_code' => 'EUR']);
});

it('mounts on the requested country with its rates and currency', function () {
    Livewire::test(HeroCalculator::class, ['initialCountry' => 'poland'])
        ->assertSet('selectedCountrySlug', 'poland')
        ->assertSet('selectedRate', 23.0)
        ->assertSet('currency', 'PLN')
        ->assertSet('rates', fn (array $rates) => array_column($rates, 'rate') === [23.0, 8.0])
        ->assertSet('rates.0.label', 'Standard');
});

it('server-renders the add VAT result before any JavaScript runs', function () {
    Livewire::test(HeroCalculator::class, ['initialCountry' => 'germany'])
        ->assertViewHas('calculation', fn ($calculation) => $calculation->net === 100.0 && $calculation->vat === 19.0 && $calculation->gross === 119.0)
        ->assertSee('€119.00');
});

it('records settled calculations in the session history', function () {
    Livewire::test(HeroCalculator::class, ['initialCountry' => 'poland'])
        ->call('calculate', 'include', 23, '100')
        ->assertSet('history.0.net', 81.3)
        ->assertSet('history.0.vat', 18.7)
        ->assertSet('history.0.gross', 100.0)
        ->assertSet('history.0.mode', 'include')
        ->assertSet('history.0.currency', 'PLN');

    expect(session('hero_calc_history'))->toHaveCount(1);
});

it('parses localised amounts when recording a calculation', function () {
    app()->setLocale('de');

    Livewire::test(HeroCalculator::class, ['initialCountry' => 'germany'])
        ->call('calculate', 'exclude', 19, '1.234,56')
        ->assertSet('history.0.amount', 1234.56)
        ->assertSet('history.0.gross', 1469.13);
});

it('ignores empty, invalid and negative amounts', function (mixed $amount) {
    Livewire::test(HeroCalculator::class, ['initialCountry' => 'germany'])
        ->call('calculate', 'exclude', 19, $amount)
        ->assertSet('history', []);
})->with(['', 'abc', '-100', '0']);

it('de-duplicates and caps the calculation history', function () {
    $component = Livewire::test(HeroCalculator::class, ['initialCountry' => 'germany']);

    foreach ([100, 100, 200, 300, 400, 500, 600, 700] as $amount) {
        $component->call('calculate', 'exclude', 19, (string) $amount);
    }

    $component->assertSet('history', fn (array $history) => count($history) === 6
        && array_map('floatval', array_column($history, 'amount')) === [700.0, 600.0, 500.0, 400.0, 300.0, 200.0]);

    $component->call('clearHistory')->assertSet('history', []);
    expect(session('hero_calc_history'))->toBeNull();
});

it('switches country, currency and rate options', function () {
    Livewire::test(HeroCalculator::class, ['initialCountry' => 'germany'])
        ->call('selectCountry', 'poland')
        ->assertSet('selectedCountrySlug', 'poland')
        ->assertSet('selectedRate', 23.0)
        ->assertSet('currency', 'PLN')
        ->assertSet('errorMessage', null);
});

it('starts a new country at its standard rate even when it shares the lower rate picked before', function () {
    Country::factory()->withRates(20, 7)->create(['name' => 'Austria', 'slug' => 'austria', 'iso_code' => 'AT', 'currency_code' => 'EUR']);

    Livewire::test(HeroCalculator::class, ['initialCountry' => 'germany', 'initialRate' => '7'])
        ->assertSet('selectedRate', 7.0)
        ->call('selectCountry', 'austria')
        ->assertSet('selectedRate', 20.0);
});

it('rejects countries that are not available in the calculator', function () {
    Country::factory()->create(['name' => 'Canada', 'slug' => 'canada', 'iso_code' => 'CA', 'standard_rate' => 5, 'is_eu_member' => false]);

    Livewire::test(HeroCalculator::class, ['initialCountry' => 'germany'])
        ->call('selectCountry', 'canada')
        ->assertSet('selectedCountrySlug', 'germany')
        ->assertSet('errorMessage', 'That country is not available in this VAT calculator.')
        ->set('selectedCountrySlug', 'canada')
        ->assertSet('selectedCountrySlug', 'germany')
        ->set('selectedCountrySlug', 'poland')
        ->assertSet('selectedCountrySlug', 'poland')
        ->assertSet('currency', 'PLN');
});

it('does not let the browser tamper with server-owned state', function (string $property, mixed $value) {
    Livewire::test(HeroCalculator::class, ['initialCountry' => 'germany'])->set($property, $value);
})->with([
    'rate' => ['selectedRate', 99],
    'rates' => ['rates', []],
    'history' => ['history', [['key' => 'x']]],
])->throws(CannotUpdateLockedPropertyException::class);

it('lets an explicit initial country override the remembered one', function () {
    $this->withCookie('hero_calc_country', 'germany');

    Livewire::test(HeroCalculator::class, ['initialCountry' => 'poland'])
        ->assertSet('selectedCountrySlug', 'poland');
});

it('restores a shared calculation, turning an unknown rate into a custom rate', function () {
    Livewire::test(HeroCalculator::class, ['initialCountry' => 'germany', 'initialAmount' => '250.5', 'initialRate' => '7', 'initialMode' => 'include'])
        ->assertSet('mode', 'include')
        ->assertSet('amount', '250.50')
        ->assertSet('selectedRate', 7.0)
        ->assertSet('customRate', null);

    Livewire::test(HeroCalculator::class, ['initialCountry' => 'germany', 'initialAmount' => '100', 'initialRate' => '12.5'])
        ->assertSet('selectedRate', 19.0)
        ->assertSet('customRate', 12.5)
        ->assertViewHas('calculation', fn ($calculation) => $calculation->vat === 12.5);
});

it('groups EU and other European countries in the country picker', function () {
    config()->set('calculator.additional_country_slugs', ['norway']);

    Country::factory()->create(['name' => 'Norway', 'slug' => 'norway', 'iso_code' => 'NO', 'standard_rate' => 25, 'is_eu_member' => false, 'vies_available' => false]);
    Country::factory()->create(['name' => 'Canada', 'slug' => 'canada', 'iso_code' => 'CA', 'standard_rate' => 5, 'is_eu_member' => false, 'vies_available' => false]);

    $countries = Livewire::test(HeroCalculator::class)->instance()->countries;

    expect(array_column($countries, 'slug'))->toBe(['germany', 'poland', 'norway'])
        ->and(array_column($countries, 'group'))->toBe(['eu', 'eu', 'other_europe']);
});

it('renders the compact embed variant without history or share-page navigation', function () {
    Livewire::test(HeroCalculator::class, ['initialCountry' => 'germany', 'surface' => 'embed', 'variant' => 'compact'])
        ->assertSet('variant', 'compact')
        ->assertSee('target="_blank"', false)
        ->assertDontSee('Full Calculator');
});

it('renders a disabled state when no calculator countries are available', function () {
    Country::query()->delete();
    Cache::flush();

    Livewire::test(HeroCalculator::class)
        ->assertSee('VAT calculator is temporarily unavailable')
        ->assertDontSee('app-button-primary', false);
});
