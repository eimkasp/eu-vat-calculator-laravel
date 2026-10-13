<?php

use App\Livewire\ViesValidatorPage;
use App\Models\Country;
use App\Models\VatValidationLog;
use App\Services\ViesValidationService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;

beforeEach(function () {
    Cache::flush();

    Country::factory()->create(['name' => 'Lithuania', 'slug' => 'lithuania', 'iso_code' => 'LT', 'standard_rate' => 21]);
    Country::factory()->create(['name' => 'Germany', 'slug' => 'germany', 'iso_code' => 'DE', 'standard_rate' => 19]);
    Country::factory()->create(['name' => 'Norway', 'slug' => 'norway', 'iso_code' => 'NO', 'standard_rate' => 25, 'is_eu_member' => false, 'vies_available' => false]);
});

function fakeVies(array $body, int $status = 200): void
{
    Http::fake(['ec.europa.eu/*' => Http::response($body, $status)]);
}

it('renders the validator with FAQ structured data', function () {
    $response = $this->get('/vat-number-validator')->assertOk()->assertSee('EU VAT Number Validator: Free VIES Check');

    preg_match_all('#<script type="application/ld\+json">(.*?)</script>#s', $response->getContent(), $matches);
    $types = collect($matches[1])->map(fn (string $json) => json_decode($json, true, flags: JSON_THROW_ON_ERROR))->pluck('@type');

    expect($types)->toContain('FAQPage')->toContain('WebApplication');
});

it('serves country pages, keeps non-VIES countries honest and rejects unknown slugs', function () {
    $this->get('/vat-number-validator/germany')->assertOk()->assertSee('Germany VAT Number Validator');

    $this->get('/vat-number-validator/norway')
        ->assertOk()
        ->assertSee('Norway is not covered by VIES')
        ->assertDontSee('NO followed by the national identifier');

    $this->get('/vat-number-validator/atlantis')->assertNotFound();
});

it('does not auto-validate or reflect malformed VAT numbers from the URL', function () {
    Http::fake();

    $this->get('/vat-number-validator?country_code=LT&vat_number=%27-alert(1)-%27')
        ->assertOk()
        ->assertDontSee("'-alert(1)-'", false)
        ->assertDontSee('data-validation-result', false);

    Http::assertNothingSent();
});

it('validates a VAT number and records a single lookup', function () {
    fakeVies(['valid' => true, 'name' => 'UAB "BusinessPress"', 'address' => 'N/A', 'requestIdentifier' => 'WAPIAAAA']);

    Livewire::test(ViesValidatorPage::class)
        ->set('vat_number', 'LT100019070512')
        ->call('validateVat')
        ->assertHasNoErrors()
        ->assertSet('country_code', 'LT')
        ->assertSet('result.valid', true)
        ->assertSet('result.name', 'UAB "BusinessPress"')
        ->assertSet('result.address', null)
        ->assertSet('result.source', 'live')
        ->assertSet('result.checked', now()->toDateString())
        ->assertDispatched('validation-complete', cc: 'LT', prefix: 'LT', vn: '100019070512', valid: true)
        ->assertSee('Valid VAT number')
        ->assertSee('Not shared by the member state');

    expect(VatValidationLog::query()->count())->toBe(1);
});

it('reuses a recent result without calling VIES again', function () {
    fakeVies(['valid' => false, 'name' => '---', 'address' => '---', 'requestIdentifier' => '']);

    Livewire::test(ViesValidatorPage::class)
        ->set('country_code', 'DE')
        ->set('vat_number', '123456789')
        ->call('validateVat')
        ->assertSet('result.valid', false)
        ->call('validateVat')
        ->assertSet('result.source', 'recent')
        ->assertSee('Not a valid VAT number')
        ->assertSee('Checked in VIES:');

    Http::assertSentCount(1);
    expect(VatValidationLog::query()->count())->toBe(2);
});

it('explains member-state outages instead of reporting the number as invalid', function () {
    fakeVies(['actionSucceed' => false, 'errorWrappers' => [['error' => 'MS_UNAVAILABLE']]]);

    Livewire::test(ViesValidatorPage::class)
        ->set('country_code', 'DE')
        ->set('vat_number', '123456789')
        ->call('validateVat')
        ->assertSet('result', null)
        ->assertSet('error', 'The VIES service is temporarily unavailable. Please try again shortly.');

    expect(Cache::get('vat_validation_DE_123456789'))->toBeNull()
        ->and(VatValidationLog::query()->count())->toBe(0);
});

it('reports numbers that VIES rejects as malformed', function () {
    fakeVies(['actionSucceed' => false, 'errorWrappers' => [['error' => 'INVALID_INPUT']]]);

    Livewire::test(ViesValidatorPage::class)
        ->set('country_code', 'LT')
        ->set('vat_number', '12345')
        ->call('validateVat')
        ->assertSet('error', 'VIES rejected this number because its format does not match Lithuania VAT numbers.');
});

it('rejects unsupported countries and malformed numbers before calling VIES', function () {
    Http::fake();

    Livewire::test(ViesValidatorPage::class)
        ->set('country_code', 'NO')
        ->set('vat_number', '123456789')
        ->call('validateVat')
        ->assertHasErrors(['country_code'])
        ->set('country_code', 'LT')
        ->set('vat_number', '<script>')
        ->call('validateVat')
        ->assertHasErrors(['vat_number']);

    Http::assertNothingSent();
});

it('rate limits lookups per visitor', function () {
    fakeVies(['valid' => true, 'name' => 'ACME', 'address' => 'Vilnius']);
    RateLimiter::increment('vies-page:127.0.0.1', 60, 20);

    Livewire::test(ViesValidatorPage::class)
        ->set('country_code', 'LT')
        ->set('vat_number', '100019070512')
        ->call('validateVat')
        ->assertSet('result', null)
        ->assertSet('error', 'Too many lookups in a short time. Please wait a minute and try again.');

    Http::assertNothingSent();
});

it('falls back to the stored result when VIES is down', function () {
    Http::fake(['ec.europa.eu/*' => Http::sequence()
        ->push(['valid' => true, 'name' => 'ACME', 'address' => 'Vilnius'])
        ->push(['actionSucceed' => false, 'errorWrappers' => [['error' => 'MS_MAX_CONCURRENT_REQ']]])]);

    app(ViesValidationService::class)->validate('LT', '100019070512');

    Cache::flush();
    $this->travel(8)->days();

    $result = app(ViesValidationService::class)->validate('LT', '100019070512');

    expect($result['source'])->toBe('database_fallback')
        ->and($result['valid'])->toBeTrue()
        ->and($result['name'])->toBe('ACME');
});
