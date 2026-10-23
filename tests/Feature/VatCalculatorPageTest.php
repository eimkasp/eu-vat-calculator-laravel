<?php

use App\Models\Country;

beforeEach(function () {
    // Create test countries
    Country::factory()->create([
        'name' => 'Germany',
        'slug' => 'germany',
        'iso_code' => 'DE',
        'standard_rate' => 19,
        'reduced_rate' => 7,
        'super_reduced_rate' => null,
        'parking_rate' => null,
    ]);

    Country::factory()->create([
        'name' => 'France',
        'slug' => 'france',
        'iso_code' => 'FR',
        'standard_rate' => 20,
        'reduced_rate' => 10,
        'super_reduced_rate' => 2.1,
        'parking_rate' => null,
    ]);
});

it('loads the main vat calculator page', function () {
    $this->get('/vat-calculator')
        ->assertStatus(200)
        ->assertSee('European')
        ->assertSee('VAT Calculator')
        ->assertSee('Calculate VAT');
});

it('loads country specific calculator page', function () {
    $country = Country::where('slug', 'germany')->first();

    $this->get("/vat-calculator/{$country->slug}")
        ->assertStatus(200)
        ->assertSee($country->name.' VAT Calculator')
        ->assertSee('Current standard rate is')
        ->assertSee($country->standard_rate.'%');
});

it('displays current vat rates section on country page', function () {
    $country = Country::where('slug', 'germany')->first();

    $this->get("/vat-calculator/{$country->slug}")
        ->assertStatus(200)
        ->assertSee('Germany VAT rates and formulas')
        ->assertSee($country->standard_rate.'%');
});

it('displays link to country guide', function () {
    $country = Country::where('slug', 'germany')->first();

    $this->get("/vat-calculator/{$country->slug}")
        ->assertStatus(200)
        ->assertSee($country->name.' VAT guide');
});

it('returns 404 for invalid country slug', function () {
    $this->get('/vat-calculator/invalid-country-slug')
        ->assertStatus(404);
});

it('loads configured non-EU calculator pages and rejects unsupported countries', function () {
    config()->set('calculator.additional_country_slugs', ['norway']);
    cache()->forget('all_countries_with_flags');
    cache()->forget('calculator_countries_v2');

    Country::factory()->create([
        'name' => 'Norway',
        'slug' => 'norway',
        'iso_code' => 'NO',
        'standard_rate' => 25,
        'is_eu_member' => false,
    ]);
    Country::factory()->create([
        'name' => 'Canada',
        'slug' => 'canada',
        'iso_code' => 'CA',
        'standard_rate' => 5,
        'is_eu_member' => false,
    ]);

    $this->get('/vat-calculator/norway')->assertOk();
    $this->get('/vat-calculator/canada')->assertNotFound();
});

it('displays breadcrumbs on main calculator page', function () {
    $this->get('/vat-calculator')
        ->assertStatus(200)
        ->assertSee('VAT Calculator');
});

it('displays breadcrumbs on country calculator page', function () {
    $country = Country::where('slug', 'france')->first();

    $this->get("/vat-calculator/{$country->slug}")
        ->assertStatus(200)
        ->assertSee('VAT Calculator')
        ->assertSee($country->name);
});

it('shows multiple vat rates for country with reduced rates', function () {
    $country = Country::where('slug', 'france')->first();

    $this->get("/vat-calculator/{$country->slug}")
        ->assertStatus(200)
        ->assertSee('France VAT rates and formulas')
        ->assertSee($country->standard_rate.'%')
        ->assertSee($country->reduced_rate.'%')
        ->assertSee($country->super_reduced_rate.'%');
});

it('displays europe map component', function () {
    $this->get('/vat-calculator')
        ->assertStatus(200)
        ->assertSee('class="eu-map"', false)
        ->assertSee('data-iso="FR"', false);
});

it('displays vat calculator form component', function () {
    $this->get('/vat-calculator')
        ->assertStatus(200)
        ->assertSeeLivewire('hero-calculator');
});

it('displays saved searches component', function () {
    $this->get('/vat-calculator')
        ->assertStatus(200);
});

it('has proper seo meta tags on country page', function () {
    $country = Country::where('slug', 'germany')->first();

    $this->get("/vat-calculator/{$country->slug}")
        ->assertStatus(200)
        ->assertSee($country->name.' VAT Calculator', false)
        ->assertSee($country->standard_rate.'% Standard Rate', false);
});

it('renders country specific seo and heading for calculator slug pages', function () {
    $luxembourg = Country::factory()->create([
        'name' => 'Luxembourg',
        'slug' => 'luxembourg',
        'iso_code' => 'LU',
        'standard_rate' => 17,
    ]);

    $this->get('/vat-calculator/luxembourg')
        ->assertStatus(200)
        ->assertSee('<title>Luxembourg VAT Calculator — 17% Standard Rate', false)
        ->assertSee('<meta name="title" content="Luxembourg VAT Calculator — 17% Standard Rate', false)
        ->assertSee('<link rel="canonical" href="https://vat.businesspress.io/vat-calculator/'.$luxembourg->slug.'">', false)
        ->assertSee('Luxembourg')
        ->assertSee('VAT Calculator')
        ->assertSee('Current standard rate is 17%')
        ->assertDontSee('<title>Germany VAT Calculator', false);
});

it('displays schema.org json-ld on country page', function () {
    $country = Country::where('slug', 'france')->first();

    $response = $this->get("/vat-calculator/{$country->slug}")
        ->assertStatus(200);

    expect($response->getContent())
        ->toContain('application/ld+json')
        ->toContain('WebApplication')
        ->toContain('FinanceApplication')
        ->toContain($country->name);
});

it('renders country calculators as a compact reference workspace', function () {
    $response = $this->get('/vat-calculator/germany')
        ->assertOk()
        ->assertSee('data-country-atmosphere', false)
        ->assertSee('data-country-header', false)
        ->assertSee('x-data="rateField(', false)
        ->assertSee('data-calculator-surface="country-image"', false)
        ->assertSee('data-country-reference', false)
        ->assertSee('Germany VAT guide')
        ->assertDontSee('Full Calculator');

    $html = $response->getContent();

    expect($html)
        ->toContain('data-country-atmosphere class="hero-field"')
        ->toContain('<div class="hero-ground" aria-hidden="true"')
        ->not->toContain('hero-texture-')
        ->and(strpos($html, 'data-country-header'))
        ->toBeLessThan(strpos($html, 'id="hero-calculator"'))
        ->and(strpos($html, 'id="hero-calculator"'))
        ->toBeLessThan(strpos($html, 'data-country-reference'));
});

it('renders a grouped country directory without a horizontal carousel', function () {
    config()->set('calculator.additional_country_slugs', ['norway']);
    cache()->forget('calculator_country_directory_v2');

    Country::factory()->create([
        'name' => 'Norway',
        'slug' => 'norway',
        'iso_code' => 'NO',
        'standard_rate' => 25,
        'is_eu_member' => false,
    ]);

    $this->get('/vat-calculator/germany')
        ->assertOk()
        ->assertSee('data-country-directory', false)
        ->assertSee('Browse all country calculators')
        ->assertSee('European Union')
        ->assertSee('Other European countries')
        ->assertDontSee('snap-x')
        ->assertDontSee('overflow-x-auto');
});

it('reserves mobile navigation clearance for calculator results', function () {
    $this->get('/vat-calculator/germany')
        ->assertOk()
        ->assertSee('mobile-nav-safe', false)
        ->assertSee('id="hero-calculator" class="scroll-mb-24', false);
});
