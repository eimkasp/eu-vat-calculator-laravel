<?php

use App\Models\Country;

beforeEach(function () {
    config()->set('app.data_domain', 'eu-vat.info');
    config()->set('app.plausible_script', 'https://stats.businesspress.io/js/script.tagged-events.outbound-links.js');
});

it('reports the main site to its own Plausible site, not the embed widget site', function () {
    $this->app['env'] = 'production';

    $this->get('/')
        ->assertOk()
        ->assertSee('<script defer data-cfasync="false" data-domain="vat.businesspress.io" src="https://stats.businesspress.io/js/script.tagged-events.outbound-links.js"></script>', false)
        ->assertDontSee('data-domain="eu-vat.info"', false);
});

it('lets PLAUSIBLE_DOMAIN rename the main site', function () {
    $this->app['env'] = 'production';
    config()->set('app.plausible_domain', 'example.com');

    $this->get('/')->assertSee('data-domain="example.com"', false);
});

it('keeps the embed widget on the DATA_DOMAIN site', function () {
    $this->app['env'] = 'production';
    Country::factory()->create(['name' => 'Sweden', 'slug' => 'sweden', 'iso_code' => 'SE', 'standard_rate' => 25, 'is_eu_member' => true]);

    $this->get('/public/embed/sweden')
        ->assertOk()
        ->assertSee('data-domain="eu-vat.info"', false)
        ->assertDontSee('data-domain="vat.businesspress.io"', false);
});

it('loads no analytics outside production or without DATA_DOMAIN', function () {
    $this->get('/')->assertOk()->assertDontSee('data-domain=', false);

    $this->app['env'] = 'production';
    config()->set('app.data_domain', null);

    $this->get('/')->assertOk()->assertDontSee('data-domain=', false);
});
