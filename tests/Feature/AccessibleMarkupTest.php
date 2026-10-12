<?php

use App\Livewire\ViesValidatorPage;
use App\Models\Country;
use Illuminate\Support\Facades\Http;
use Livewire\Features\SupportDisablingBackButtonCache\SupportDisablingBackButtonCache;
use Livewire\Livewire;

beforeEach(function () {
    Country::factory()->create(['name' => 'Germany', 'slug' => 'germany', 'iso_code' => 'DE', 'standard_rate' => 19, 'reduced_rate' => 7, 'is_eu_member' => true]);
    Country::factory()->create(['name' => 'Lithuania', 'slug' => 'lithuania', 'iso_code' => 'LT', 'standard_rate' => 21, 'is_eu_member' => true]);
});

afterEach(function () {
    SupportDisablingBackButtonCache::$disableBackButtonCache = false;
});

it('tells screen readers which links open a new tab, in the page language, and leaves it out of Markdown', function () {
    $this->get('/de')
        ->assertOk()
        ->assertSee('<span class="sr-only" data-markdown-skip> (öffnet in einem neuen Tab)</span>', false)
        ->assertSee('aria-label="'.__('ui.nav.github', [], 'de').' (öffnet in einem neuen Tab)"', false);

    expect($this->get('/', ['Accept' => 'text/markdown'])->getContent())->not->toContain('opens in a new tab');
});

it('ties validator errors to their fields', function () {
    Http::fake();

    Livewire::test(ViesValidatorPage::class)
        ->set('country_code', 'LT')
        ->set('vat_number', '<script>')
        ->call('validateVat')
        ->assertHasErrors(['vat_number'])
        ->assertSeeHtml('id="vat-number-error"')
        ->assertSeeHtml('aria-describedby="vat-number-error vat-number-hint vat-number-format-hint"');
});

it('lets keyboard users reach every table that scrolls sideways', function (string $path) {
    $html = $this->get($path)->assertOk()->getContent();

    preg_match_all('/<div class="relative[^"]*overflow-x-auto[^"]*"[^>]*>/', $html, $wrappers);

    expect($wrappers[0])->not->toBeEmpty();

    foreach ($wrappers[0] as $wrapper) {
        expect($wrapper)->toContain('tabindex="0"')->toContain('role="region"')->toMatch('/aria-label(ledby)?="[^"]+"/');
    }
})->with(['/', '/vat-map', '/vat-number-validator', '/vat-validation-api', '/top-vat-calculations']);

it('gives the add or remove VAT switch one tab stop with arrow keys', function () {
    $this->get('/vat-calculator/germany')
        ->assertOk()
        ->assertSee('@keydown.arrow-right.prevent="$rove($event)"', false)
        ->assertSee('tabindex="0"', false)
        ->assertSee(':tabindex="mode === ', false);
});

it('switches language with plain links instead of a select that navigates on change', function () {
    $this->get('/')->assertOk()->assertDontSee('onchange=', false)->assertSee('id="mobile-language-menu"', false);
});
