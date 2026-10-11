<?php

use App\Models\Country;
use App\Models\VatRate;
use App\Models\VatRateChange;
use App\Models\VatRateRule;
use App\Services\SitemapGenerator;
use App\Support\Seo\SeoPolicy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

it('canonicalizes pages with English-only bodies to the English URL in every language', function (string $path, string $canonical) {
    $this->get($path)
        ->assertOk()
        ->assertSee('<meta name="robots" content="index, follow">', false)
        ->assertSee('<link rel="canonical" href="'.$canonical.'">', false)
        ->assertSee('<meta property="og:url" content="'.$canonical.'">', false)
        ->assertDontSee('hreflang=', false);
})->with([
    'privacy policy' => ['/de/privacy', 'https://vat.businesspress.io/privacy'],
    'changelog' => ['/fr/changelog', 'https://vat.businesspress.io/changelog'],
    'donate' => ['/es/donate', 'https://vat.businesspress.io/donate'],
    'Chrome extension' => ['/it/chrome-extension', 'https://vat.businesspress.io/chrome-extension'],
    'API documentation' => ['/nl/vat-validation-api', 'https://vat.businesspress.io/vat-validation-api'],
    'blog index' => ['/pl/blog', 'https://vat.businesspress.io/blog'],
    'blog post' => ['/de/blog/upcoming-vat-changes-2026-2027', 'https://vat.businesspress.io/blog/upcoming-vat-changes-2026-2027'],
    'design system' => ['/sv/styleguide', 'https://vat.businesspress.io/styleguide'],
    'the English page itself' => ['/privacy', 'https://vat.businesspress.io/privacy'],
]);

it('keeps translated pages self-canonical with hreflang in every language', function (string $path) {
    $this->get($path)
        ->assertOk()
        ->assertSee('<meta name="robots" content="index, follow">', false)
        ->assertSee('<link rel="canonical" href="https://vat.businesspress.io'.$path.'">', false)
        ->assertSee('hreflang="x-default"', false);
})->with(['/de/tools', '/fr/mcp-server', '/hu/top-vat-calculations', '/sl/top-vat-calculations/1000']);

it('only names routes that exist', function () {
    foreach (SeoPolicy::ENGLISH_BODY_ROUTES as $name) {
        expect(Route::has($name))->toBeTrue("{$name} is not a route")
            ->and(Route::has('locale.'.$name))->toBeTrue("locale.{$name} is not a route");
    }

    $policy = app(SeoPolicy::class);

    expect($policy->isTranslatedRoute('locale.vat-changes.event'))->toBeFalse()
        ->and($policy->isTranslatedRoute('vat-changes.event'))->toBeFalse()
        ->and($policy->isTranslatedRoute('vat-calculator.country'))->toBeTrue()
        ->and($policy->isTranslatedRoute('locale.top-calculations.amount'))->toBeTrue()
        ->and($policy->isTranslatedRoute(null))->toBeTrue();
});

it('lists a sitemap URL in every language only when its page is translated', function () {
    $countries = collect([['Germany', 'germany', 'DE'], ['France', 'france', 'FR'], ['Spain', 'spain', 'ES']])->map(
        fn (array $row) => Country::factory()->create(['name' => $row[0], 'slug' => $row[1], 'iso_code' => $row[2], 'standard_rate' => 20, 'is_eu_member' => true, 'vies_available' => true])
    );

    foreach ($countries as $country) {
        VatRate::create(['country_id' => $country->id, 'type' => 'standard', 'rate' => 19, 'effective_from' => '2020-01-01', 'source' => 'Official archive']);
        VatRateChange::factory()->create(['country_id' => $country->id, 'rate_type' => 'standard', 'change_date' => '2026-01-01']);
        VatRateChange::factory()->create(['country_id' => $country->id, 'rate_type' => 'reduced', 'change_date' => now()->addMonths(3)->toDateString()]);
        VatRateRule::create([
            'country_id' => $country->id, 'category_slug' => 'books', 'category_name' => 'Books', 'rate_type' => 'reduced', 'rate' => 7,
            'effective_from' => now()->subYear()->toDateString(), 'effective_to' => null, 'legal_basis' => 'National VAT Act',
            'source_url' => 'https://example.gov/books-vat', 'verified_at' => now()->subDay(), 'published_at' => now()->subDay(),
        ]);
    }

    $policy = app(SeoPolicy::class);
    $languages = count($policy->indexableLocales());
    $locales = array_keys(config('translation.supported_languages'));
    $listed = [];

    foreach (SitemapGenerator::SECTIONS as $section) {
        preg_match_all('#<loc>https://vat\.businesspress\.io(/[^<]*)</loc>#', $this->get("/sitemaps/{$section}.xml")->assertOk()->getContent(), $matches);

        foreach ($matches[1] as $url) {
            $path = preg_replace('#^/('.implode('|', $locales).')(?=/|$)#', '', $url) ?: '/';
            $listed[$path] = ($listed[$path] ?? 0) + 1;
        }
    }

    expect(array_keys($listed))->toContain(
        '/', '/top-vat-calculations/1000', '/vat-calculator/germany', '/vat-number-validator/france', '/vat-changes', '/vat-rates/germany/history',
        '/vat-changes/germany/standard/2026-01-01', '/vat-changes/year/2026', '/vat-changes/upcoming', '/vat-rates/categories', '/vat-rates/categories/books',
        '/vat-rates/spain/categories/books', '/vat-guides/b2b-services', '/blog', '/blog/upcoming-vat-changes-2026-2027', '/datasets/eu-vat-rates',
        '/vat-validation-api', '/styleguide', '/vat-calculation/germany/1000/20/exclude', '/vat-calculation/germany/100/20/include'
    );

    foreach ($listed as $path => $count) {
        $route = app('router')->getRoutes()->match(Request::create($path === '/' ? '/' : $path));
        $expected = $policy->isTranslatedRoute($route->getName()) ? $languages : 1;

        expect($count)->toBe($expected, "{$path} ({$route->getName()}) is listed in {$count} languages, expected {$expected}");
    }
});
