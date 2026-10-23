<?php

use App\Models\Country;
use App\Models\VatRateChange;
use App\Services\Seo\InternalLinkService;

function comparisonCountry(string $name, string $slug, string $iso, float $rate): Country
{
    return Country::factory()->create([
        'name' => $name,
        'slug' => $slug,
        'iso_code' => $iso,
        'standard_rate' => $rate,
        'is_eu_member' => true,
    ]);
}

it('publishes only allowlisted VAT country comparisons', function () {
    $germany = comparisonCountry('Germany', 'germany', 'DE', 19);
    $france = comparisonCountry('France', 'france', 'FR', 20);
    VatRateChange::factory()->for($germany)->create();
    VatRateChange::factory()->for($france)->create();

    $this->get('/compare/germany-vs-france-vat')
        ->assertOk()
        ->assertSee('<link rel="canonical" href="https://vat.businesspress.io/compare/germany-vs-france-vat">', false)
        ->assertSee('Germany vs France VAT rates')
        ->assertSee('19%')
        ->assertSee('20%')
        ->assertSee('1 pp higher')
        ->assertSee('/vat-rates/germany/history')
        ->assertSee('/vat-rates/france/history');

    $this->get('/compare/germany-vs-hungary-vat')->assertNotFound();
});

it('redirects a reversed approved comparison to one canonical order', function () {
    comparisonCountry('Germany', 'germany', 'DE', 19);
    comparisonCountry('France', 'france', 'FR', 20);

    $this->get('/compare/france-vs-germany-vat')
        ->assertRedirect('/compare/germany-vs-france-vat')
        ->assertStatus(301);
});

it('returns not found when an approved comparison country is unavailable', function () {
    comparisonCountry('Germany', 'germany', 'DE', 19);

    $this->get('/compare/germany-vs-france-vat')->assertNotFound();
});

it('selects related countries by VAT-rate relevance instead of alphabetically', function () {
    $germany = comparisonCountry('Germany', 'germany', 'DE', 19);
    comparisonCountry('Austria', 'austria', 'AT', 20);
    comparisonCountry('France', 'france', 'FR', 20);
    comparisonCountry('Netherlands', 'netherlands', 'NL', 21);
    comparisonCountry('Hungary', 'hungary', 'HU', 27);

    $related = app(InternalLinkService::class)->relatedCountries($germany, 3);

    expect($related->pluck('slug')->all())->toBe(['austria', 'france', 'netherlands']);
});

it('links country hubs to history and approved comparisons', function () {
    $germany = comparisonCountry('Germany', 'germany', 'DE', 19);
    comparisonCountry('France', 'france', 'FR', 20);
    VatRateChange::factory()->for($germany)->create();

    $this->get('/vat-calculator/germany')
        ->assertOk()
        ->assertSee('/vat-rates/germany/history')
        ->assertSee('/compare/germany-vs-france-vat');
});

it('does not link to a country history without stored historical data', function () {
    comparisonCountry('Germany', 'germany', 'DE', 19);
    comparisonCountry('France', 'france', 'FR', 20);

    $this->get('/vat-calculator/germany')
        ->assertOk()
        ->assertDontSee('/vat-rates/germany/history');
});

it('lists the approved comparisons on one hub and links it across the site', function () {
    comparisonCountry('Germany', 'germany', 'DE', 19);
    comparisonCountry('France', 'france', 'FR', 20);
    comparisonCountry('Spain', 'spain', 'ES', 21);
    comparisonCountry('Portugal', 'portugal', 'PT', 23);

    $this->get('/compare')
        ->assertOk()
        ->assertSee('<link rel="canonical" href="https://vat.businesspress.io/compare">', false)
        ->assertSee('Compare VAT rates between EU countries')
        ->assertSee('href="/compare/germany-vs-france-vat"', false)
        ->assertSee('href="/compare/spain-vs-portugal-vat"', false)
        ->assertSee('Germany vs France')
        ->assertSee('1 pp higher')
        ->assertSee('"@type":"ItemList"', false)
        ->assertDontSee('/compare/germany-vs-netherlands-vat');

    $this->get('/compare/germany-vs-france-vat')
        ->assertOk()
        ->assertSee('More comparisons')
        ->assertSee('href="/compare/spain-vs-portugal-vat"', false)
        ->assertSee('href="/compare"', false);

    $this->get('/')
        ->assertOk()
        ->assertSee('Popular comparisons')
        ->assertSee('href="/compare/germany-vs-france-vat"', false)
        ->assertSee('href="/compare"', false);

    $this->get('/sitemap')->assertOk()->assertSee('href="/compare/spain-vs-portugal-vat"', false);
    $this->getJson('/search-index.json')->assertOk()->assertJsonFragment(['url' => '/compare/germany-vs-france-vat']);
});
