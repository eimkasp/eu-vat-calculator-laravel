<?php

use App\Livewire\TopCalculations;
use App\Models\Country;
use App\Models\VatRate;
use App\Models\VatRateChange;
use App\Support\Vat\CountryFaq;
use App\Support\Vat\VatNumberFormat;

function redesignGermany(array $overrides = []): Country
{
    return Country::factory()->create(array_merge([
        'name' => 'Germany', 'slug' => 'germany', 'iso_code' => 'DE', 'standard_rate' => 19, 'reduced_rate' => 7,
        'currency_code' => 'EUR', 'is_eu_member' => true, 'vies_available' => true, 'updated_at' => '2026-10-01 08:00:00',
    ], $overrides));
}

it('shows when rates were updated and where they come from next to the calculator and the rate table', function () {
    redesignGermany();
    Country::factory()->create(['name' => 'Norway', 'slug' => 'norway', 'iso_code' => 'NO', 'standard_rate' => 25, 'is_eu_member' => false, 'vies_available' => false]);

    $germany = $this->get('/vat-calculator/germany')->assertOk()->getContent();
    $norway = $this->get('/vat-calculator/norway')->assertOk()->getContent();

    expect(substr_count($germany, 'data-rate-freshness'))->toBeGreaterThanOrEqual(2)
        ->and($germany)->toContain('Rates updated <time datetime="2026-10-01">1 Oct 2026</time>')
        ->toContain('href="'.config('calculator.rates_source_url').'"')
        ->not->toContain('Always up to date')
        ->and($norway)->toContain('href="'.config('calculator.dataset_source_url').'"')
        ->not->toContain('href="'.config('calculator.rates_source_url').'"');
});

it('offers one-tap common amounts in the calculator', function () {
    redesignGermany();

    $html = $this->get('/vat-calculator/germany')->assertOk()->assertSee('Common amounts')->getContent();

    foreach ([100, 500, 1000, 5000, 10000] as $amount) {
        expect($html)->toContain('@click="setAmount('.$amount.')"');
    }
});

it('links every common amount to its indexable calculation page in both directions', function () {
    redesignGermany();

    $response = $this->get('/vat-calculator/germany')->assertOk()->assertSee('Germany VAT on common amounts');

    foreach (TopCalculations::AMOUNTS as $amount) {
        $response->assertSee('href="/vat-calculation/germany/'.$amount.'/19/exclude"', false)
            ->assertSee('href="/vat-calculation/germany/'.$amount.'/19/include"', false);
    }

    $response->assertSee('€11,900.00')->assertSee('€8,403.36');
});

it('dates the standard rate from the rate history and shows the VAT number format', function () {
    $germany = redesignGermany();
    VatRate::create(['country_id' => $germany->id, 'type' => 'standard', 'rate' => 16, 'effective_from' => '2020-07-01', 'effective_to' => '2021-01-01', 'source' => 'test']);
    VatRate::create(['country_id' => $germany->id, 'type' => 'standard', 'rate' => 19, 'effective_from' => '2021-01-01', 'source' => 'test']);

    $this->get('/vat-calculator/germany')
        ->assertOk()
        ->assertSee('In force since 1 Jan 2021, replacing 16%')
        ->assertSee('DE999999999');
});

it('does not date a standard rate the history disagrees with', function () {
    $germany = redesignGermany(['standard_rate' => 20]);
    VatRate::create(['country_id' => $germany->id, 'type' => 'standard', 'rate' => 19, 'effective_from' => '2021-01-01', 'source' => 'test']);

    expect($germany->standardRateSince())->toBeNull();
    $this->get('/vat-calculator/germany')->assertOk()->assertDontSee('In force since');
});

it('answers country questions from the data and publishes the same answers as FAQPage data', function () {
    $germany = redesignGermany();
    VatRateChange::factory()->create([
        'country_id' => $germany->id, 'rate_type' => 'standard', 'old_rate' => 16, 'new_rate' => 19,
        'change_date' => '2021-01-01', 'change_direction' => 'increase',
    ]);

    $faqs = collect(CountryFaq::for($germany->fresh()));

    expect($faqs->pluck('a')->all())->toBe([
        'The standard VAT rate in Germany is 19%. Reduced rates of 7% apply to selected goods and services.',
        'Multiply the net price by 1.19. For example, €100.00 plus 19% VAT is €119.00.',
        'Divide the price including VAT by 1.19. For example, €100.00 including 19% VAT is €84.03 before VAT.',
        'The standard rate changed from 16% to 19% on 1 January 2021.',
        "VAT numbers from Germany start with DE and follow the format DE999999999, where 9 is a digit, L a letter and X a letter or a digit. You can check one against the EU's VIES register with the VAT number validator.",
    ]);

    $html = $this->get('/vat-calculator/germany')->assertOk()->getContent();
    preg_match_all('#<script type="application/ld\+json">(.*?)</script>#s', $html, $blocks);
    $faqPage = collect($blocks[1])->map(fn (string $json) => json_decode($json, true))->firstWhere('@type', 'FAQPage');

    expect($faqPage['mainEntity'])->toHaveCount(5)
        ->and(collect($faqPage['mainEntity'])->pluck('name')->all())->toBe($faqs->pluck('q')->all());

    foreach ($faqs as $faq) {
        expect($html)->toContain(e($faq['q']));
    }
});

it('skips the questions a country has no data for', function () {
    $denmark = Country::factory()->create(['name' => 'Denmark', 'slug' => 'denmark', 'iso_code' => 'DK', 'standard_rate' => 25, 'reduced_rate' => null, 'is_eu_member' => true, 'vies_available' => true]);
    $norway = Country::factory()->create(['name' => 'Norway', 'slug' => 'norway', 'iso_code' => 'NO', 'standard_rate' => 25, 'is_eu_member' => false, 'vies_available' => false]);

    expect(CountryFaq::for($denmark)[0]['a'])->toBe('The standard VAT rate in Denmark is 25%, and Denmark has no reduced rate.')
        ->and(CountryFaq::for($denmark))->toHaveCount(4)
        ->and(CountryFaq::for($norway))->toHaveCount(3);
});

it('describes every member state VAT number format with a pattern that accepts its own masks', function () {
    $formats = config('vat-number-formats');

    expect($formats)->toHaveCount(count(Country::EU_MEMBER_CODES))
        ->and(array_keys($formats))->toEqualCanonicalizing(Country::EU_MEMBER_CODES);

    foreach ($formats as $code => $format) {
        foreach ($format['masks'] as $mask) {
            $example = strtr(substr($mask, 2), ['9' => '1', 'L' => 'A', 'X' => '1']);
            $number = substr($mask, 0, 2).$example;

            expect(VatNumberFormat::matches($format, $number))->toBeTrue("{$code}: {$number} should match {$format['pattern']}");
        }

        $longest = collect($format['masks'])->sortByDesc(fn (string $mask) => strlen($mask))->first();
        $tooLong = substr($longest, 0, 2).strtr(substr($longest, 2), ['9' => '1', 'L' => 'A', 'X' => '1']).'1';
        expect(VatNumberFormat::matches($format, $tooLong))->toBeFalse("{$code}: {$tooLong} should not match");

        expect($format['prefix'])->toBe($code === 'GR' ? 'EL' : $code);
    }

    expect(VatNumberFormat::matches($formats['DE'], 'de 811 569 869'))->toBeTrue()
        ->and(VatNumberFormat::describe($formats['RO']))->toBe('RO99 … RO9999999999')
        ->and(VatNumberFormat::describe($formats['CZ']))->toBe('CZ99999999 · CZ999999999 · CZ9999999999');
});

it('lists every member state VAT number format on the validator and hints at typing mistakes', function () {
    foreach ([['Germany', 'germany', 'DE'], ['Greece', 'greece', 'GR'], ['Romania', 'romania', 'RO']] as [$name, $slug, $iso]) {
        Country::factory()->create(['name' => $name, 'slug' => $slug, 'iso_code' => $iso, 'is_eu_member' => true, 'vies_available' => true]);
    }

    $this->get('/vat-number-validator')
        ->assertOk()
        ->assertSee('VAT number formats in the EU')
        ->assertSee('EL999999999')
        ->assertSee('RO99 … RO9999999999')
        ->assertSee('href="/vat-number-validator/greece"', false)
        ->assertSee('This does not look like a VAT number from :country (:format)', false)
        ->assertSee('x-on:blur="checkFormat($event.target.value)"', false);

    $this->get('/vat-number-validator/germany')
        ->assertOk()
        ->assertSee('DE999999999')
        ->assertSee('9 is a digit, L a letter and X a letter or a digit.')
        ->assertDontSee('VAT number formats in the EU');
});

it('aligns the country calculator with the page edges and puts the flag in the kicker', function () {
    redesignGermany();

    $country = $this->get('/vat-calculator/germany')->assertOk()->getContent();
    $generic = $this->get('/vat-calculator')->assertOk()->getContent();

    preg_match('/<div id="hero-calculator" class="([^"]+)"/', $country, $countryCard);
    preg_match('/<div id="hero-calculator" class="([^"]+)"/', $generic, $genericCard);
    preg_match('/<header data-country-header.*?<\/h1>/s', $country, $heading);

    expect($countryCard[1])->not->toContain('max-w-5xl')
        ->and($genericCard[1])->toContain('mx-auto max-w-5xl')
        ->and($heading[0])->toMatch('/<p class="flex items-center gap-3">(?:(?!<\/p>).)*flags\/de\.svg(?:(?!<\/p>).)*<span class="app-kicker sm:before:hidden">/s');
});
