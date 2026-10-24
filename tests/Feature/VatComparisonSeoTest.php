<?php

use App\Livewire\VatComparison;
use App\Models\Country;
use App\Models\VatRateChange;
use App\Services\Seo\InternalLinkService;
use App\Services\SitemapGenerator;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;

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

it('indexes the approved VAT country comparisons', function () {
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
    $this->get('/compare/germany-vs-france-vat')->assertSee('<meta name="robots" content="index, follow">', false);
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
        ->assertSee('Compare VAT rates between European countries')
        ->assertSee('href="/compare/germany-vs-france-vat"', false)
        ->assertSee('href="/compare/spain-vs-portugal-vat"', false)
        ->assertSee('Germany vs France')
        ->assertSee('France is 1 pp higher')
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
    $this->get('/de/compare')->assertOk()->assertSee('Germany vs. France')->assertSee('France: 1 Pp. höher');
    $this->get('/llms.txt')->assertOk()->assertSee('/compare): The approved EU country pairs', false);
    $this->getJson('/search-index.json')->assertOk()->assertJsonFragment(['url' => '/compare/germany-vs-france-vat']);
});

it('compares any two calculator countries on one canonical, unindexed URL', function () {
    comparisonCountry('Estonia', 'estonia', 'EE', 24);
    comparisonCountry('Latvia', 'latvia', 'LV', 21);

    $this->get('/compare/latvia-vs-estonia-vat')
        ->assertRedirect('/compare/estonia-vs-latvia-vat')
        ->assertStatus(301);

    $this->get('/compare/estonia-vs-latvia-vat')
        ->assertOk()
        ->assertSee('Estonia vs Latvia VAT rates')
        ->assertSee('<link rel="canonical" href="https://vat.businesspress.io/compare/estonia-vs-latvia-vat">', false)
        ->assertSee('<meta name="robots" content="noindex, follow">', false)
        ->assertSee('3 pp higher')
        ->assertDontSee('hreflang=', false);
});

it('compares three countries, including a non-EU calculator country', function () {
    comparisonCountry('Germany', 'germany', 'DE', 19);
    comparisonCountry('France', 'france', 'FR', 20);
    Country::factory()->create([
        'name' => 'Norway', 'slug' => 'norway', 'iso_code' => 'NO', 'standard_rate' => 25,
        'is_eu_member' => false, 'vies_available' => false,
    ]);

    $this->get('/compare/norway-vs-germany-vs-france-vat')
        ->assertRedirect('/compare/france-vs-germany-vs-norway-vat')
        ->assertStatus(301);

    $this->get('/compare/france-vs-germany-vs-norway-vat')
        ->assertOk()
        ->assertSee('France, Germany and Norway VAT rates')
        ->assertSee('<meta name="robots" content="noindex, follow">', false)
        ->assertSee('19%')
        ->assertSee('20%')
        ->assertSee('25%')
        ->assertSee('Highest')
        ->assertSee('Lowest')
        ->assertSee('/vat-calculator/norway');
});

it('rejects repeated, missing, unavailable and four-country comparisons', function () {
    comparisonCountry('Germany', 'germany', 'DE', 19);
    comparisonCountry('France', 'france', 'FR', 20);
    comparisonCountry('Spain', 'spain', 'ES', 21);
    comparisonCountry('Italy', 'italy', 'IT', 22);
    Country::factory()->create(['name' => 'Atlantis', 'slug' => 'atlantis', 'iso_code' => 'AQ', 'standard_rate' => 10, 'is_eu_member' => false]);

    $this->get('/compare/germany-vs-germany-vat')->assertNotFound();
    $this->get('/compare/france-vs-narnia-vat')->assertNotFound();
    $this->get('/compare/narnia-vs-france-vat')->assertNotFound();
    $this->get('/compare/germany-vs-france-vs--vat')->assertNotFound();
    $this->get('/compare/atlantis-vs-france-vat')->assertNotFound();
    $this->get('/compare/france-vs-germany-vs-italy-vs-spain-vat')->assertNotFound();
});

it('sends the picker to the canonical comparison and explains an incomplete choice', function () {
    comparisonCountry('Germany', 'germany', 'DE', 19);
    comparisonCountry('France', 'france', 'FR', 20);
    comparisonCountry('Estonia', 'estonia', 'EE', 24);

    $this->get('/compare?countries[]=france&countries[]=germany')->assertRedirect('/compare/germany-vs-france-vat');
    $this->get('/compare?countries[]=germany&countries[]=estonia&countries[]=')->assertRedirect('/compare/estonia-vs-germany-vat');
    $this->get('/compare?countries[]=germany&countries[]=france&countries[]=estonia')->assertRedirect('/compare/estonia-vs-france-vs-germany-vat');
    $this->get('/de/compare?countries[]=germany&countries[]=france')->assertRedirect('/de/compare/germany-vs-france-vat');

    $this->get('/compare?countries[]=germany&countries[]=france&countries[]=narnia')->assertRedirect('/compare/germany-vs-france-vat');
    $this->get('/compare?countries[]=&countries[]=france')
        ->assertOk()
        ->assertSee('<option value="france" selected', false);

    $this->get('/compare?countries[]=germany&countries[]=germany')
        ->assertOk()
        ->assertSee('<title>Choose at least two different countries.', false)
        ->assertSee('aria-invalid="true"', false)
        ->assertSee('autofocus', false)
        ->assertSee('Choose at least two different countries.')
        ->assertSee('<meta name="robots" content="noindex, follow">', false)
        ->assertSee('<option value="germany" selected', false);

    $this->get('/compare')
        ->assertOk()
        ->assertSee('Compare any two or three countries')
        ->assertSee('name="countries[]"', false)
        ->assertSee('<meta name="robots" content="index, follow">', false);

    $this->get('/compare/germany-vs-france-vat')->assertSee('Compare other countries')->assertSee('<option value="germany" selected', false);
});

it('keeps only the approved pairs in the XML sitemap', function () {
    comparisonCountry('Germany', 'germany', 'DE', 19);
    comparisonCountry('France', 'france', 'FR', 20);
    comparisonCountry('Estonia', 'estonia', 'EE', 24);

    $generator = app(SitemapGenerator::class);
    $xml = collect(SitemapGenerator::SECTIONS)->map(fn (string $section) => $generator->generateSection($section))->implode('');

    expect($xml)->toContain('/compare/germany-vs-france-vat')
        ->not->toContain('/compare/estonia-vs-');
});

it('keeps the compared countries server-owned', function () {
    comparisonCountry('Germany', 'germany', 'DE', 19);
    comparisonCountry('France', 'france', 'FR', 20);

    Livewire\Livewire::test(VatComparison::class, ['pair' => 'germany-vs-france'])
        ->set('slugs', ['germany']);
})->throws(CannotUpdateLockedPropertyException::class);

it('credits both sources when EU and other European countries are compared', function () {
    comparisonCountry('Germany', 'germany', 'DE', 19);
    Country::factory()->create([
        'name' => 'Norway', 'slug' => 'norway', 'iso_code' => 'NO', 'standard_rate' => 25,
        'is_eu_member' => false, 'vies_available' => false,
    ]);

    $this->get('/compare/germany-vs-norway-vat')
        ->assertOk()
        ->assertSee(config('calculator.rates_source_url'), false)
        ->assertSee(config('calculator.dataset_source_url'), false);
});

it('keeps the country picker out of the Markdown version', function () {
    comparisonCountry('Germany', 'germany', 'DE', 19);
    comparisonCountry('France', 'france', 'FR', 20);

    foreach (['/compare', '/compare/germany-vs-france-vat'] as $url) {
        expect($this->get($url, ['Accept' => 'text/markdown'])->getContent())
            ->toContain('Germany')
            ->not->toContain('Germany (19%)')
            ->not->toContain('Compare other countries');
    }
});
