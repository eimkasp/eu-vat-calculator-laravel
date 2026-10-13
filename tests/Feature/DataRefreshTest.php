<?php

use App\Jobs\GenerateVatRateChanges;
use App\Jobs\VerifyVatRatesIntegrity;
use App\Models\Country;
use App\Models\VatRate;
use App\Models\VatRateChange;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ── VAT History Page ──────────────────────────────────────────────────────

it('loads vat-changes page with correct layout', function () {
    $this->get('/vat-changes')
        ->assertStatus(200)
        ->assertSee('VAT Rate Changes History')
        ->assertSee('Country stability')
        ->assertSee('All countries');
});

it('vat-changes page shows country filter', function () {
    $country = Country::factory()->create(['name' => 'TestCountry', 'iso_code' => 'TC']);
    VatRateChange::factory()->for($country)->create(['rate_type' => 'standard', 'old_rate' => 20, 'new_rate' => 21]);
    VatRateChange::factory()->for($country)->create(['rate_type' => 'reduced', 'old_rate' => 9, 'new_rate' => 10]);

    $this->get('/vat-changes')
        ->assertStatus(200)
        ->assertSee('All countries')
        ->assertSee('TestCountry')
        ->assertSee('All types')
        ->assertSee('Standard');
});

it('vat-changes page hides the type filter when only one rate type has changes', function () {
    $country = Country::factory()->create(['name' => 'TestCountry', 'iso_code' => 'TC']);
    VatRateChange::factory()->for($country)->create(['rate_type' => 'standard', 'old_rate' => 20, 'new_rate' => 21]);

    $this->get('/vat-changes')
        ->assertStatus(200)
        ->assertSee('TestCountry')
        ->assertDontSee('All types');
});

it('vat-changes page shows empty state when no changes', function () {
    $this->get('/vat-changes')
        ->assertStatus(200)
        ->assertSee('No VAT rate changes found');
});

// ── Navigation links to vat-changes ──────────────────────────────────────

it('header navigation includes vat history link', function () {
    $response = $this->get('/');
    $response->assertStatus(200);
    expect($response->getContent())->toContain('VAT history');
});

it('homepage widget has full history link', function () {
    $response = $this->get('/');
    $response->assertStatus(200);
    expect($response->getContent())->toContain('Full history');
});

// ── Data Refresh Jobs ──────────────────────────────────────────────────────

it('verify integrity job syncs all rate types', function () {
    $country = Country::factory()->create([
        'iso_code' => 'XX',
        'standard_rate' => 15.0,
        'reduced_rate' => 5.0,
    ]);

    // Create VatRate with different standard rate
    VatRate::create([
        'country_id' => $country->id,
        'type' => 'standard',
        'rate' => 20.0,
        'effective_from' => now()->subYear(),
        'source' => 'test',
    ]);

    $job = new VerifyVatRatesIntegrity;
    $job->handle();

    $country->refresh();
    expect((float) $country->standard_rate)->toBe(20.0);
});

it('generate changes job creates change records', function () {
    $country = Country::factory()->create(['iso_code' => 'YY']);

    VatRate::create([
        'country_id' => $country->id,
        'type' => 'standard',
        'rate' => 18.0,
        'effective_from' => '2020-01-01',
        'source' => 'test',
    ]);

    VatRate::create([
        'country_id' => $country->id,
        'type' => 'standard',
        'rate' => 20.0,
        'effective_from' => '2022-01-01',
        'source' => 'test',
    ]);

    $job = new GenerateVatRateChanges;
    $job->handle();

    $changes = VatRateChange::where('country_id', $country->id)->get();
    expect($changes)->toHaveCount(1);
    expect((float) $changes->first()->old_rate)->toBe(18.0);
    expect((float) $changes->first()->new_rate)->toBe(20.0);
    expect($changes->first()->change_direction)->toBe('increase');
});

// ── Artisan vat:refresh command ─────────────────────────────────────────

it('vat:refresh command is registered', function () {
    $this->artisan('vat:refresh --help')
        ->assertExitCode(0);
});

it('vat:refresh command has expected options', function () {
    $this->artisan('vat:refresh --help')
        ->expectsOutputToContain('skip-download')
        ->expectsOutputToContain('no-changes')
        ->expectsOutputToContain('queue')
        ->assertExitCode(0);
});

// ── Scheduled jobs ──────────────────────────────────────────────────────

it('vat jobs are scheduled in console routes', function () {
    $consoleContent = file_get_contents(base_path('routes/console.php'));
    expect($consoleContent)->toContain('UpdateVatRates')
        ->toContain('VerifyVatRatesIntegrity')
        ->toContain('GenerateVatRateChanges')
        ->toContain('weeklyOn')
        ->toContain('dailyAt');
});
