<?php

use App\Services\Seo\IndexNowService;
use Illuminate\Support\Facades\Http;
use Livewire\Features\SupportDisablingBackButtonCache\SupportDisablingBackButtonCache;

afterEach(function () {
    SupportDisablingBackButtonCache::$disableBackButtonCache = false;
});

it('keeps IndexNow disabled until credentials are configured', function () {
    Http::fake();
    config()->set('seo.indexnow.enabled', false);

    expect(app(IndexNowService::class)->submit(['https://eu-vat.info/vat-changes']))->toBeFalse();
    Http::assertNothingSent();
});

it('submits only canonical-host URLs in an IndexNow payload', function () {
    Http::fake(['https://api.indexnow.org/indexnow' => Http::response('', 200)]);
    config()->set('seo.indexnow.enabled', true);
    config()->set('seo.indexnow.key', 'seo-test-key');

    $result = app(IndexNowService::class)->submit([
        'https://eu-vat.info/vat-changes',
        'https://eu-vat.info/vat-changes',
        'https://example.com/not-ours',
    ]);

    expect($result)->toBeTrue();
    Http::assertSent(fn ($request) => $request->url() === 'https://api.indexnow.org/indexnow'
        && $request['host'] === 'eu-vat.info'
        && $request['key'] === 'seo-test-key'
        && $request['keyLocation'] === 'https://eu-vat.info/indexnow-key.txt'
        && $request['urlList'] === ['https://eu-vat.info/vat-changes']);
});

it('serves the configured IndexNow key only when enabled', function () {
    config()->set('seo.indexnow.enabled', true);
    config()->set('seo.indexnow.key', 'seo-test-key');

    $this->get('/indexnow-key.txt')
        ->assertOk()
        ->assertSeeText('seo-test-key');

    config()->set('seo.indexnow.enabled', false);
    SupportDisablingBackButtonCache::$disableBackButtonCache = true;
    $response = $this->get('/indexnow-key.txt')->assertNotFound();

    expect($response->headers->get('Cache-Control'))->not->toContain('public');
});

it('passes the repository SEO audit and rejects legacy canonical configuration', function () {
    $this->artisan('seo:audit')->assertSuccessful();

    config()->set('seo.canonical_url', 'https://vat.businesspress.io');
    $this->artisan('seo:audit')->assertFailed();
});
