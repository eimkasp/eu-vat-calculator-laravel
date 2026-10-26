<?php

use App\Support\DesignSystem\TokenDocument;

it('shows the mark, the token colours, the type and the voice', function () {
    $tokens = TokenDocument::current()->toArray()['color'];

    $this->get('/brand')
        ->assertOk()
        ->assertSee('The EU VAT Info brand')
        ->assertSee(strtoupper($tokens['semantic']['brand']['$value']['hex']))
        ->assertSee(strtoupper($tokens['semantic']['action']['$value']['hex']))
        ->assertSee('Geist')
        ->assertSee('Rates updated 11 Oct 2026, from the European Commission')
        ->assertSee('CC BY 4.0')
        ->assertSee('href="'.url('/brand/eu-vat-info-mark.svg').'" download="eu-vat-info-mark.svg"', false)
        ->assertSee('href="'.url('/styleguide').'"', false)
        ->assertSee('href="'.url('/storybook/').'"', false)
        ->assertSee('<link rel="canonical" href="https://vat.businesspress.io/brand">', false);
});

it('keeps the English guidelines and their English canonical in other languages', function () {
    $this->get('/de/brand')
        ->assertOk()
        ->assertSee(__('ui.brand.english_only', [], 'de'))
        ->assertSee('<link rel="canonical" href="https://vat.businesspress.io/brand">', false)
        ->assertDontSee('hreflang=', false);
});

it('serves the mark as a standalone SVG drawn by the logo component', function () {
    $svg = $this->get('/brand/eu-vat-info-mark.svg')
        ->assertOk()
        ->assertHeader('Content-Type', 'image/svg+xml')
        ->getContent();

    expect($svg)->toStartWith('<?xml')
        ->toContain('xmlns="http://www.w3.org/2000/svg"')
        ->toContain('width="512" height="512"')
        ->toContain('fill="#0462d3"')
        ->not->toContain('class=')
        ->not->toContain('aria-hidden');
});

it('is linked from the footer, the site map, quick search, llms.txt and the XML sitemap', function () {
    $this->get('/')->assertOk()->assertSee('href="/brand"', false);
    $this->get('/sitemap')->assertOk()->assertSee(__('ui.brand.nav_desc'));
    $this->getJson('/search-index.json?locale=en')->assertJsonFragment(['url' => '/brand']);
    $this->get('/llms.txt')->assertSee('/brand')->assertSee('/storybook/');
    $this->get('/sitemaps/core.xml')
        ->assertOk()
        ->assertSee('<loc>https://vat.businesspress.io/brand</loc>', false)
        ->assertDontSee('https://vat.businesspress.io/de/brand', false);
});
