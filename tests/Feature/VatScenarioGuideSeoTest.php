<?php

it('publishes finite source-backed VAT scenario guides', function () {
    $this->get('/vat-guides/b2b-services')
        ->assertOk()
        ->assertSee('<link rel="canonical" href="https://eu-vat.info/vat-guides/b2b-services">', false)
        ->assertSee('EU B2B services VAT guide')
        ->assertSee('Article 44')
        ->assertSee('Article 196')
        ->assertSee('taxation-customs.ec.europa.eu/taxation/vat/vat-directive/place-taxation_en')
        ->assertSee('"@type":"Article"', false);
});

it('rejects unapproved scenario URL permutations', function () {
    $this->get('/vat-guides/germany-to-france-b2b-services')->assertNotFound();
});

it('includes every approved scenario guide in the core sitemap', function () {
    $response = $this->get('/sitemaps/core.xml')->assertOk();

    foreach (array_keys(config('vat-scenarios')) as $slug) {
        $response->assertSee('/vat-guides/'.$slug);
    }
});
