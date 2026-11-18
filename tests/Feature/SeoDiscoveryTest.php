<?php

use App\Models\Country;
use Carbon\CarbonImmutable;
use Livewire\Features\SupportDisablingBackButtonCache\SupportDisablingBackButtonCache;

afterEach(function () {
    SupportDisablingBackButtonCache::$disableBackButtonCache = false;
});

it('publishes one canonical sitemap and no unsupported crawl delay directives', function () {
    $robots = file_get_contents(public_path('robots.txt'));

    expect(substr_count($robots, 'Sitemap:'))->toBe(1)
        ->and($robots)->toContain('Sitemap: https://eu-vat.info/sitemap.xml')
        ->not->toContain('vat.businesspress.io')
        ->not->toContain('Crawl-delay:');
});

it('serves a sitemap index with focused canonical sections', function () {
    $this->get('/sitemap.xml')
        ->assertOk()
        ->assertHeader('Content-Type', 'application/xml')
        ->assertSee('<sitemapindex', false)
        ->assertSee('https://eu-vat.info/sitemaps/core.xml', false)
        ->assertSee('https://eu-vat.info/sitemaps/countries.xml', false)
        ->assertSee('https://eu-vat.info/sitemaps/validators.xml', false)
        ->assertSee('https://eu-vat.info/sitemaps/changes.xml', false)
        ->assertSee('https://eu-vat.info/sitemaps/editorial.xml', false);
});

it('emits every ready locale as a loc with reciprocal hreflang and truthful lastmod', function () {
    config()->set('seo.indexable_locales', ['en', 'de']);

    $country = Country::factory()->create([
        'name' => 'Germany',
        'slug' => 'germany',
        'iso_code' => 'DE',
        'is_eu_member' => true,
    ]);
    $country->timestamps = false;
    $country->forceFill(['updated_at' => CarbonImmutable::parse('2026-06-12T09:30:00+00:00')])->saveQuietly();

    $response = $this->get('/sitemaps/countries.xml');

    $response
        ->assertOk()
        ->assertSee('<loc>https://eu-vat.info/vat-calculator/germany</loc>', false)
        ->assertSee('<loc>https://eu-vat.info/de/vat-calculator/germany</loc>', false)
        ->assertSee('hreflang="en" href="https://eu-vat.info/vat-calculator/germany"', false)
        ->assertSee('hreflang="de" href="https://eu-vat.info/de/vat-calculator/germany"', false)
        ->assertSee('<lastmod>2026-06-12T', false)
        ->assertDontSee('<priority>', false)
        ->assertDontSee('<changefreq>', false);
});

it('serves current LLM documentation from the canonical host', function () {
    Country::factory()->create([
        'name' => 'Germany',
        'slug' => 'germany',
        'iso_code' => 'DE',
        'standard_rate' => 19,
        'is_eu_member' => true,
    ]);

    $this->get('/llms.txt')
        ->assertOk()
        ->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
        ->assertSee('https://eu-vat.info/vat-calculator/germany')
        ->assertSee('https://eu-vat.info/vat-number-validator/germany')
        ->assertDontSee('vat.businesspress.io')
        ->assertDontSee('/country/germany');
});

it('preserves public discovery caching after Livewire has booted', function () {
    SupportDisablingBackButtonCache::$disableBackButtonCache = true;

    $response = $this->get('/sitemap.xml')->assertOk();

    expect($response->headers->get('Cache-Control'))
        ->toContain('public')
        ->toContain('max-age=3600');
});
