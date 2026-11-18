<?php

use App\Models\Country;
use App\Models\VatRateRule;
use App\Services\Seo\VatCategorySeoService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

function categoryCountry(string $name, string $slug, string $iso, bool $eu = true): Country
{
    return Country::factory()->create([
        'name' => $name,
        'slug' => $slug,
        'iso_code' => $iso,
        'is_eu_member' => $eu,
    ]);
}

function categoryRule(Country $country, array $overrides = []): VatRateRule
{
    return VatRateRule::create(array_merge([
        'country_id' => $country->id,
        'category_slug' => 'books',
        'category_name' => 'Books',
        'rate_type' => 'reduced',
        'rate' => 7,
        'effective_from' => now()->subYear()->toDateString(),
        'effective_to' => null,
        'legal_basis' => 'National VAT Act, books provision',
        'source_url' => 'https://example.gov/books-vat',
        'verified_at' => now()->subDay(),
        'published_at' => now()->subDay(),
    ], $overrides));
}

it('limits current category rules to their effective date range', function () {
    $country = categoryCountry('Germany', 'germany', 'DE');
    $current = categoryRule($country);
    categoryRule($country, [
        'category_slug' => 'future-books',
        'effective_from' => now()->addMonth()->toDateString(),
    ]);
    categoryRule($country, [
        'category_slug' => 'expired-books',
        'effective_to' => now()->subMonth()->toDateString(),
    ]);
    categoryRule($country, [
        'category_slug' => 'undated-books',
        'effective_from' => null,
    ]);

    expect(VatRateRule::indexable()->current()->pluck('id')->all())->toBe([$current->id]);
});

it('rejects malformed category source URLs at write time and from existing rows', function () {
    $country = categoryCountry('Germany', 'germany', 'DE');
    $valid = categoryRule($country);

    foreach (['', 'javascript:alert(1)', 'example.gov/books', 'https://', 'https://not a valid host'] as $source) {
        expect(fn () => categoryRule($country, [
            'category_slug' => 'invalid-'.md5($source),
            'source_url' => $source,
        ]))->toThrow(ValidationException::class);
    }

    DB::table('vat_rate_rules')->insert([
        'country_id' => $country->id,
        'category_slug' => 'legacy-invalid-source',
        'category_name' => 'Legacy invalid source',
        'rate_type' => 'reduced',
        'rate' => 7,
        'effective_from' => now()->subYear()->toDateString(),
        'source_url' => 'https://not a valid host',
        'verified_at' => now()->subDay(),
        'published_at' => now()->subDay(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    expect(VatRateRule::indexable()->current()->pluck('id')->all())->toBe([$valid->id]);
});

it('uses only the latest current country rule in category aggregates', function () {
    config()->set('seo.category_minimum_country_coverage', 3);

    $germany = categoryCountry('Germany', 'germany', 'DE');
    categoryRule($germany, [
        'rate' => 30,
        'effective_from' => now()->subYears(2)->toDateString(),
        'verified_at' => now(),
    ]);
    categoryRule($germany, [
        'rate' => 7,
        'effective_from' => now()->subYear()->toDateString(),
        'verified_at' => now()->subDay(),
    ]);
    categoryRule(categoryCountry('France', 'france', 'FR'), ['rate' => 5.5]);
    categoryRule(categoryCountry('Spain', 'spain', 'ES'), ['rate' => 4]);

    $books = app(VatCategorySeoService::class)->eligibleCategories()->firstWhere('slug', 'books');

    expect($books['country_count'])->toBe(3)
        ->and($books['minimum_rate'])->toBe(4.0)
        ->and($books['maximum_rate'])->toBe(7.0)
        ->and((string) $books['last_verified_at'])->toContain(now()->subDay()->toDateString());
});

it('qualifies category hubs only after verified EU country coverage reaches the threshold', function () {
    config()->set('seo.category_minimum_country_coverage', 3);

    foreach ([
        ['Germany', 'germany', 'DE'],
        ['France', 'france', 'FR'],
        ['Spain', 'spain', 'ES'],
    ] as [$name, $slug, $iso]) {
        categoryRule(categoryCountry($name, $slug, $iso));
    }

    categoryRule(categoryCountry('Switzerland', 'switzerland', 'CH', false));
    categoryRule(categoryCountry('Italy', 'italy', 'IT'), [
        'category_slug' => 'food',
        'category_name' => 'Food',
        'source_url' => null,
    ]);

    $categories = app(VatCategorySeoService::class)->eligibleCategories();

    expect($categories)->toHaveCount(1)
        ->and($categories->first()['slug'])->toBe('books')
        ->and($categories->first()['country_count'])->toBe(3);
});

it('publishes a category directory and source-backed comparison hub after the coverage threshold', function () {
    config()->set('seo.category_minimum_country_coverage', 3);

    foreach ([
        ['Germany', 'germany', 'DE', 7],
        ['France', 'france', 'FR', 5.5],
        ['Spain', 'spain', 'ES', 4],
    ] as [$name, $slug, $iso, $rate]) {
        categoryRule(categoryCountry($name, $slug, $iso), [
            'rate' => $rate,
            'source_url' => "https://example.gov/{$slug}/books-vat",
        ]);
    }

    $this->get('/vat-rates/categories')
        ->assertOk()
        ->assertSee('<link rel="canonical" href="https://eu-vat.info/vat-rates/categories">', false)
        ->assertSee('/vat-rates/categories/books')
        ->assertSee('3 EU countries');

    $this->get('/vat-rates/categories/books')
        ->assertOk()
        ->assertSee('<link rel="canonical" href="https://eu-vat.info/vat-rates/categories/books">', false)
        ->assertSee('Books VAT rates across the EU')
        ->assertSee('Germany')
        ->assertSee('7%')
        ->assertSee('https://example.gov/germany/books-vat')
        ->assertSee('"@type":"CollectionPage"', false)
        ->assertSee('"@type":"ItemList"', false);
});

it('does not publish category directories or hubs below the coverage threshold', function () {
    config()->set('seo.category_minimum_country_coverage', 3);

    categoryRule(categoryCountry('Germany', 'germany', 'DE'));
    categoryRule(categoryCountry('France', 'france', 'FR'));

    $this->get('/vat-rates/categories')->assertNotFound();
    $this->get('/vat-rates/categories/books')->assertNotFound();
});

it('publishes a sourced country-category detail page even before hub coverage is reached', function () {
    $country = categoryCountry('Germany', 'germany', 'DE');
    categoryRule($country, [
        'rate' => 7,
        'verified_at' => now()->subDay()->startOfDay(),
    ]);

    $this->get('/vat-rates/germany/categories/books')
        ->assertOk()
        ->assertSee('<link rel="canonical" href="https://eu-vat.info/vat-rates/germany/categories/books">', false)
        ->assertSee('Books VAT rate in Germany')
        ->assertSee('7%')
        ->assertSee('National VAT Act, books provision')
        ->assertSee('https://example.gov/books-vat')
        ->assertSee(now()->subDay()->startOfDay()->format('j F Y'))
        ->assertSee('transaction-specific tax advice')
        ->assertSee('"@type":"WebPage"', false)
        ->assertSee('"@type":"DefinedTerm"', false)
        ->assertSee('/vat-calculator/germany');
});

it('returns not found for a country-category rule that is not publishable', function () {
    $country = categoryCountry('Germany', 'germany', 'DE');
    categoryRule($country, ['verified_at' => null]);

    $this->get('/vat-rates/germany/categories/books')->assertNotFound();
});

it('discovers only eligible category URLs and links them from country tools', function () {
    config()->set('seo.category_minimum_country_coverage', 3);

    foreach ([
        ['Germany', 'germany', 'DE'],
        ['France', 'france', 'FR'],
        ['Spain', 'spain', 'ES'],
    ] as [$name, $slug, $iso]) {
        categoryRule(categoryCountry($name, $slug, $iso));
    }

    categoryRule(categoryCountry('Italy', 'italy', 'IT'), [
        'category_slug' => 'food',
        'category_name' => 'Food',
        'published_at' => null,
    ]);

    $this->get('/sitemap.xml')
        ->assertOk()
        ->assertSee('/sitemaps/categories.xml');

    $this->get('/sitemaps/categories.xml')
        ->assertOk()
        ->assertSee('https://eu-vat.info/vat-rates/categories')
        ->assertSee('https://eu-vat.info/vat-rates/categories/books')
        ->assertSee('https://eu-vat.info/vat-rates/germany/categories/books')
        ->assertDontSee('/categories/food');

    $this->get('/llms.txt')
        ->assertOk()
        ->assertSee('VAT categories')
        ->assertSee('https://eu-vat.info/vat-rates/categories/books');

    $this->get('/vat-calculator/germany')
        ->assertOk()
        ->assertSee('/vat-rates/germany/categories/books')
        ->assertSee('Books VAT rate');
});
