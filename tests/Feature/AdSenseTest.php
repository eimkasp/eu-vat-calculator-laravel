<?php

use Livewire\Features\SupportDisablingBackButtonCache\SupportDisablingBackButtonCache;

afterEach(function () {
    SupportDisablingBackButtonCache::$disableBackButtonCache = false;
});

$script = '<script async data-cfasync="false" src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-1234567890123456" crossorigin="anonymous"></script>';

it('serves no ads.txt and no ads until ADSENSE_ID is set', function () {
    $this->app['env'] = 'production';

    $this->get('/ads.txt')->assertNotFound();

    $home = $this->get('/')->assertOk()
        ->assertDontSee('google-adsense-account', false)
        ->assertDontSee('adsbygoogle', false);

    expect($home->headers->get('Content-Security-Policy'))->not->toMatch('# https:(;| |$)#');
});

it('lists Google as an authorised seller in ads.txt', function (string $id) {
    config()->set('app.adsense_id', $id);
    SupportDisablingBackButtonCache::$disableBackButtonCache = true;

    $response = $this->get('/ads.txt')
        ->assertOk()
        ->assertHeader('Content-Type', 'text/plain; charset=UTF-8');

    expect($response->getContent())->toBe("google.com, pub-1234567890123456, DIRECT, f08c47fec0942fa0\n")
        ->and($response->headers->get('Cache-Control'))->toContain('public');
})->with(['ca-pub-1234567890123456', 'pub-1234567890123456', ' ca-pub-1234567890123456 ']);

it('ignores an ADSENSE_ID that is not a publisher ID', function () {
    config()->set('app.adsense_id', 'G-ABC123');

    $this->get('/ads.txt')->assertNotFound();
});

it('loads the AdSense script in production and lets its frames through the CSP', function () use ($script) {
    $this->app['env'] = 'production';
    config()->set('app.adsense_id', 'ca-pub-1234567890123456');

    $response = $this->get('/')->assertOk()
        ->assertSee('<meta name="google-adsense-account" content="ca-pub-1234567890123456">', false)
        ->assertSee($script, false);

    $csp = $response->headers->get('Content-Security-Policy');

    expect($csp)->toMatch('#script-src [^;]* https:(;|$)#')
        ->toMatch("#frame-src 'self' https:(;|$)#")
        ->toContain("object-src 'none'")
        ->toContain("frame-ancestors 'self'");
});

it('keeps the AdSense script off local and test traffic but still verifies the site', function () use ($script) {
    config()->set('app.adsense_id', 'ca-pub-1234567890123456');

    $this->get('/')->assertOk()
        ->assertSee('<meta name="google-adsense-account" content="ca-pub-1234567890123456">', false)
        ->assertDontSee($script, false);
});

it('shows no ads on the not found page', function () use ($script) {
    $this->app['env'] = 'production';
    config()->set('app.adsense_id', 'ca-pub-1234567890123456');

    $this->get('/no-such-page')->assertNotFound()->assertDontSee($script, false);
});

it('discloses advertising cookies in the privacy policy only while AdSense is on', function () {
    $this->get('/privacy')->assertOk()
        ->assertSee('We do not use advertising or third-party tracking cookies.')
        ->assertDontSee('Google AdSense')
        ->assertSee('<a href="https://eu-vat.info">eu-vat.info</a>', false);

    config()->set('app.adsense_id', 'ca-pub-1234567890123456');

    $this->get('/privacy')->assertOk()
        ->assertSee('Google AdSense')
        ->assertSee('https://adssettings.google.com', false)
        ->assertDontSee('We do not use advertising or third-party tracking cookies.');
});
