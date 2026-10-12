<?php

use App\Models\Country;
use App\Models\VatRateChange;
use Livewire\Features\SupportDisablingBackButtonCache\SupportDisablingBackButtonCache;

beforeEach(function () {
    $this->germany = Country::factory()->create(['name' => 'Germany', 'slug' => 'germany', 'iso_code' => 'DE', 'standard_rate' => 19, 'reduced_rate' => 7, 'is_eu_member' => true]);
    Country::factory()->create(['name' => 'France', 'slug' => 'france', 'iso_code' => 'FR', 'standard_rate' => 20, 'reduced_rate' => 10, 'is_eu_member' => true]);
});

afterEach(function () {
    SupportDisablingBackButtonCache::$disableBackButtonCache = false;
});

function ldBlocks(string $html): array
{
    preg_match_all('#<script type="application/ld\+json">(.*?)</script>#s', $html, $matches);

    return array_map(fn (string $json) => json_decode($json, true, flags: JSON_THROW_ON_ERROR), $matches[1]);
}

function ldTypes(string $html): array
{
    return collect(ldBlocks($html))->flatMap(fn (array $block) => $block['@graph'] ?? [$block])->pluck('@type')->flatten()->all();
}

it('describes the page language and its translations for social previews', function () {
    $this->get('/de/vat-calculator/germany')
        ->assertOk()
        ->assertSee('<meta property="og:locale" content="de_DE">', false)
        ->assertSee('<meta property="og:locale:alternate" content="en_GB">', false)
        ->assertSee('<meta property="og:locale:alternate" content="mt_MT">', false)
        ->assertDontSee('<meta property="og:locale:alternate" content="de_DE">', false)
        ->assertSee('<meta name="twitter:card" content="summary_large_image">', false)
        ->assertSee('<meta property="og:image:width" content="1200">', false)
        ->assertDontSee('property="twitter:', false)
        ->assertDontSee('article:modified_time', false);

    $this->get('/de/brand')
        ->assertOk()
        ->assertSee('<meta property="og:locale" content="en_GB">', false)
        ->assertDontSee('og:locale:alternate', false);
});

it('adds hreflang only to indexable pages that exist in every language', function () {
    $this->get('/vat-calculator/germany')->assertOk()->assertSee('<link rel="alternate" hreflang="de"', false);
    $this->get('/embed')->assertOk()->assertDontSee('<link rel="alternate" hreflang=', false);
    $this->get('/de/vat-changes?page=2')->assertOk()->assertSee('noindex', false)->assertDontSee('<link rel="alternate" hreflang=', false);
});

it('pairs AMP pages with the English pages only', function () {
    $this->get('/vat-calculator/germany')->assertSee('<link rel="amphtml" href="'.url('/amp/vat-calculator/germany').'">', false);
    $this->get('/de/vat-calculator/germany')->assertDontSee('rel="amphtml"', false);
    $this->get('/de')->assertDontSee('rel="amphtml"', false);
    $this->get('/amp/vat-calculator/germany')->assertSee('19%')->assertDontSee('19.00%');
});

it('advertises the Markdown version and leaves error pages without a canonical', function () {
    $this->get('/vat-map')->assertSee('<link rel="alternate" type="text/markdown" href="https://vat.businesspress.io/vat-map" title="Markdown">', false);
    $this->get('/no-such-page')->assertNotFound()->assertDontSee('rel="canonical"', false);
});

it('describes the visible breadcrumb trail once, without microdata', function (string $path) {
    $html = $this->get($path)->assertOk()->getContent();

    expect(array_count_values(ldTypes($html))['BreadcrumbList'] ?? 0)->toBe(1)
        ->and($html)->not->toContain('itemprop=');
})->with(['/vat-calculator/germany', '/vat-map', '/vat-changes', '/datasets/eu-vat-rates', '/vat-guides/b2b-services', '/tools', '/de/vat-number-validator']);

it('builds breadcrumbs from the trail a visitor sees, with canonical links', function () {
    $html = $this->get('/de/vat-map')->assertOk()->getContent();
    $trail = collect(ldBlocks($html))->firstWhere('@type', 'BreadcrumbList')['itemListElement'];

    expect(array_column($trail, 'position'))->toBe([1, 2])
        ->and($trail[0]['item'])->toBe('https://vat.businesspress.io/de')
        ->and($trail[1]['item'])->toBe('https://vat.businesspress.io/de/vat-map');
});

it('describes the rates dataset the same way on the home page and the dataset page', function () {
    $node = fn (string $path) => collect(ldBlocks($this->get($path)->getContent()))
        ->flatMap(fn (array $block) => $block['@graph'] ?? [$block])
        ->firstWhere('@type', 'Dataset');
    $home = collect($node('/'))->except('@context')->all();

    expect($home)->toBe($node('/datasets/eu-vat-rates'))
        ->and($home['temporalCoverage'])->toBe($this->germany->fresh()->updated_at->toDateString());
});

it('names the site with its brand and one id on every page', function () {
    $site = collect(ldBlocks($this->get('/de')->getContent()))->firstWhere('@type', 'WebSite');

    expect($site['name'])->toBe('EU VAT Info')
        ->and($site['@id'])->toBe('https://vat.businesspress.io/#website');

    $this->get('/de/vat-calculator/germany')->assertSee('"isPartOf":{"@type":"WebSite","@id":"https://vat.businesspress.io/#website"', false);
});

it('dates and describes a rate change page for search results', function () {
    VatRateChange::factory()->create([
        'country_id' => $this->germany->id,
        'rate_type' => 'standard',
        'old_rate' => 16,
        'new_rate' => 19,
        'change_date' => '2007-01-01',
        'change_direction' => 'increase',
        'description' => 'Rate changed from 16.00% to 19.00%',
    ]);

    $this->get('/vat-changes/germany/standard/2007-01-01')
        ->assertOk()
        ->assertSee('<title>Germany standard VAT: 16% to 19% on 1 Jan 2007 | EU VAT Info</title>', false)
        ->assertSee('On 1 January 2007, Germany’s standard VAT rate rose from 16% to 19%.', false)
        ->assertDontSee('"datePublished":"2007-01-01', false);
});

it('lists the footer pages and leaves empty sections out of the sitemap index', function () {
    $core = $this->get('/sitemaps/core.xml')->assertOk();

    foreach (['/changelog', '/privacy', '/donate', '/chrome-extension'] as $path) {
        $core->assertSee('<loc>https://vat.businesspress.io'.$path.'</loc>', false)
            ->assertDontSee('<loc>https://vat.businesspress.io/de'.$path.'</loc>', false);
    }

    $this->get('/sitemap.xml')->assertOk()->assertDontSee('sitemaps/categories.xml', false);
});

it('dates template pages by the latest release instead of the deploy time', function () {
    preg_match('/^## \[[^\]]+\] — (\d{4}-\d{2}-\d{2})/m', file_get_contents(base_path('CHANGELOG.md')), $release);

    $this->get('/sitemaps/core.xml')
        ->assertSee("<loc>https://vat.businesspress.io/styleguide</loc>\n        <lastmod>{$release[1]}T00:00:00+00:00</lastmod>", false);
});

it('lists the guides, the comparisons and the recent changes for AI agents', function () {
    VatRateChange::factory()->create([
        'country_id' => $this->germany->id,
        'rate_type' => 'standard',
        'old_rate' => 16,
        'new_rate' => 19,
        'change_date' => today()->subMonths(3),
    ]);

    $this->get('/llms.txt')
        ->assertOk()
        ->assertSee('## Guides')
        ->assertSee('/vat-guides/b2b-services')
        ->assertSee('## Comparisons')
        ->assertSee('[Germany vs France VAT](https://vat.businesspress.io/compare/germany-vs-france-vat)');

    $this->get('/llms-full.txt')
        ->assertOk()
        ->assertSee('## Recent VAT rate changes (last 24 months)')
        ->assertSee('/vat-changes/germany/standard/'.today()->subMonths(3)->toDateString());
});

it('links the guides from the HTML site map and the years from the rate history', function () {
    VatRateChange::factory()->create(['country_id' => $this->germany->id, 'rate_type' => 'standard', 'change_date' => '2021-01-01']);

    $this->get('/sitemap')->assertOk()->assertSee('href="/vat-guides/b2b-services"', false);
    $this->get('/vat-changes')->assertOk()->assertSee('href="/vat-changes/year/2021"', false);
});
