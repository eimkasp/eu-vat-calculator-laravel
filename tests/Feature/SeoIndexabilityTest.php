<?php

use App\Livewire\TopCalculations;
use App\Models\Country;

function indexingCountry(array $overrides = []): Country
{
    return Country::factory()->create(array_merge([
        'name' => 'Germany',
        'slug' => 'germany',
        'iso_code' => 'DE',
        'standard_rate' => 19,
        'is_eu_member' => true,
    ], $overrides));
}

it('redirects the legacy SEO host to the canonical host', function () {
    $response = $this->get('https://eu-vat.info/vat-calculator/germany?amount=100');

    $response->assertRedirect('https://vat.businesspress.io/vat-calculator/germany?amount=100');
    expect($response->getStatusCode())->toBe(301);
});

it('indexes every supported language by default and links them with hreflang', function () {
    $locales = array_keys(config('translation.supported_languages'));

    expect(config('seo.indexable_locales'))->toBe([]);

    $german = $this->get('/de')
        ->assertOk()
        ->assertSee('<meta name="robots" content="index, follow">', false)
        ->assertSee('<link rel="canonical" href="https://vat.businesspress.io/de">', false)
        ->assertSee('hreflang="x-default"', false);

    foreach ($locales as $locale) {
        $german->assertSee('hreflang="'.$locale.'"', false);
    }

    expect(substr_count($german->getContent(), '<link rel="alternate" hreflang='))->toBe(count($locales) + 1);
});

it('limits indexing to the locales named in configuration', function () {
    config()->set('seo.indexable_locales', ['en', 'de']);

    $this->get('/')
        ->assertSee('<meta name="robots" content="index, follow">', false)
        ->assertSee('hreflang="de"', false)
        ->assertDontSee('hreflang="fr"', false);

    $this->get('/fr')
        ->assertOk()
        ->assertSee('<meta name="robots" content="noindex, follow">', false)
        ->assertSee('<link rel="canonical" href="https://vat.businesspress.io/fr">', false)
        ->assertDontSee('hreflang=', false);
});

it('indexes top calculations in every language', function (string $path) {
    indexingCountry();

    $this->get($path)
        ->assertOk()
        ->assertSee('<meta name="robots" content="index, follow">', false)
        ->assertSee('<link rel="canonical" href="https://vat.businesspress.io'.$path.'">', false)
        ->assertSee('hreflang="x-default"', false)
        ->assertSee('hreflang="pt"', false);
})->with([
    'English, adding VAT' => '/vat-calculation/germany/1000/19/exclude',
    'English, removing VAT' => '/vat-calculation/germany/10000/19/include',
    'German' => '/de/vat-calculation/germany/100/19/exclude',
    'Portuguese' => '/pt/vat-calculation/germany/2500/19/include',
]);

it('keeps arbitrary calculations out of the index', function (string $path) {
    indexingCountry();
    indexingCountry(['name' => 'Switzerland', 'slug' => 'switzerland', 'iso_code' => 'CH', 'standard_rate' => 8.1, 'is_eu_member' => false]);

    $this->get($path)
        ->assertOk()
        ->assertSee('<meta name="robots" content="noindex, follow">', false)
        ->assertSee('<link rel="canonical" href="https://vat.businesspress.io/vat-calculator/'.(str_contains($path, 'switzerland') ? 'switzerland' : 'germany').'">', false)
        ->assertDontSee('hreflang=', false);
})->with([
    'an amount nobody lists' => '/vat-calculation/germany/1234.56/19/exclude',
    'a round amount that is not a top amount' => '/vat-calculation/germany/1001/19/exclude',
    'a reduced rate' => '/vat-calculation/germany/1000/7/exclude',
    'an arbitrary rate' => '/vat-calculation/germany/1000/19.5/include',
    'a translated random amount' => '/de/vat-calculation/germany/1234/19/exclude',
    'a country outside the EU at its standard rate' => '/vat-calculation/switzerland/1000/8.1/exclude',
]);

it('redirects non-canonical calculation URLs permanently', function (string $path, string $target) {
    indexingCountry();

    $response = $this->get($path);

    $response->assertRedirect($target);
    expect($response->getStatusCode())->toBe(301);
})->with([
    'trailing zeros' => ['/vat-calculation/germany/1000.00/19.00/exclude', '/vat-calculation/germany/1000/19/exclude'],
    'one trailing zero' => ['/vat-calculation/germany/1000.50/19.0/include', '/vat-calculation/germany/1000.5/19/include'],
    'a language and a query string' => ['/pt/vat-calculation/germany/100.00/19.00/exclude?utm_source=mail', '/pt/vat-calculation/germany/100/19/exclude?utm_source=mail'],
]);

it('serves canonical calculation URLs without redirecting', function () {
    indexingCountry();

    $this->get('/vat-calculation/germany/1000.5/19/exclude')->assertOk();
});

it('lists exactly the indexable top calculations in their own sitemap section', function () {
    indexingCountry();
    indexingCountry(['name' => 'Switzerland', 'slug' => 'switzerland', 'iso_code' => 'CH', 'standard_rate' => 8.1, 'is_eu_member' => false]);

    $this->get('/sitemap.xml')->assertOk()->assertSee('https://vat.businesspress.io/sitemaps/calculations.xml', false);

    $xml = $this->get('/sitemaps/calculations.xml')->assertOk()->getContent();
    preg_match_all('#<loc>https://vat\.businesspress\.io([^<]*)</loc>#', $xml, $locs);
    $english = array_values(array_filter($locs[1], fn (string $path) => str_starts_with($path, '/vat-calculation/')));

    expect($locs[1])->toHaveCount(count(TopCalculations::AMOUNTS) * 2 * count(config('translation.supported_languages')))
        ->and($english)->toHaveCount(count(TopCalculations::AMOUNTS) * 2)
        ->and($english)->toContain('/vat-calculation/germany/100/19/exclude', '/vat-calculation/germany/10000/19/include')
        ->and($xml)->not->toContain('switzerland')
        ->and($xml)->not->toContain('xhtml:link');

    foreach (['/vat-calculation/germany/100/19/exclude', '/de/vat-calculation/germany/2500/19/include'] as $path) {
        expect($locs[1])->toContain($path);
        $this->get($path)
            ->assertOk()
            ->assertSee('<meta name="robots" content="index, follow">', false)
            ->assertSee('<link rel="canonical" href="https://vat.businesspress.io'.$path.'">', false);
    }
});
