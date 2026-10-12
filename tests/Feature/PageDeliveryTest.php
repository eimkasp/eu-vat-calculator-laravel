<?php

use App\Models\Country;
use Livewire\Features\SupportDisablingBackButtonCache\SupportDisablingBackButtonCache;

beforeEach(function () {
    Country::factory()->create(['name' => 'Germany', 'slug' => 'germany', 'iso_code' => 'DE', 'standard_rate' => 19, 'reduced_rate' => 7, 'is_eu_member' => true]);
});

afterEach(function () {
    SupportDisablingBackButtonCache::$disableBackButtonCache = false;
});

it('keeps every script out of Cloudflare Rocket Loader so the theme and the calculator run on time', function (string $path) {
    $this->app['env'] = 'production';
    config()->set('app.data_domain', 'eu-vat.info');

    $html = $this->get($path)->assertOk()->getContent();

    preg_match_all('/<script\b(?![^>]*type="application\/ld\+json")[^>]*>/', $html, $scripts);

    expect($scripts[0])->not->toBeEmpty();

    foreach ($scripts[0] as $script) {
        expect($script)->toContain('data-cfasync="false"');
    }
})->with(['/', '/vat-calculator/germany', '/vat-number-validator', '/de/vat-map', '/public/embed/germany']);

it('lets public pages into the back/forward cache while they still revalidate', function (string $path) {
    $response = $this->get($path)->assertOk();

    expect($response->headers->get('Cache-Control'))->toContain('no-cache')
        ->toContain('private')
        ->not->toContain('no-store')
        ->and($response->headers->has('Expires'))->toBeFalse()
        ->and($response->headers->has('Pragma'))->toBeFalse();
})->with(['/', '/vat-calculator/germany', '/de/vat-number-validator', '/vat-map']);

it('keeps the admin panel out of every cache', function () {
    expect($this->get('/admin/login')->headers->get('Cache-Control'))->toContain('no-store');
});

it('links each language to the same page in that language', function () {
    $this->get('/de/vat-calculator/germany?amount=100')
        ->assertOk()
        ->assertSee('href="/vat-calculator/germany?amount=100" lang="en"', false)
        ->assertSee('href="/fr/vat-calculator/germany?amount=100" lang="fr"', false)
        ->assertDontSee('href="/lang/', false);

    $this->get('/embed')
        ->assertOk()
        ->assertSee('href="/de" lang="de"', false);
});

it('fits the Europe map paths inside the rendered view and keeps one shape per country', function () {
    $svg = file_get_contents(resource_path('images/europe.svg'));

    expect(strlen($svg))->toBeLessThan(40_000)
        ->and(preg_match_all('/<path d="[^"]+" id="([A-Z]{2})"/', $svg))->toBe(45);
});
