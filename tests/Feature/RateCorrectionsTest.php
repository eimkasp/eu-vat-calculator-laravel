<?php

use App\Models\Country;
use App\Support\Vat\RateCorrections;

it('replaces the January 2024 rates that have changed since', function () {
    $slovakia = Country::factory()->create(['iso_code' => 'SK', 'reduced_rate' => '10', 'super_reduced_rate' => 5, 'parking_rate' => null]);
    $finland = Country::factory()->create(['iso_code' => 'FI', 'reduced_rate' => '10 / 14', 'super_reduced_rate' => null]);
    $malta = Country::factory()->create(['iso_code' => 'MT', 'reduced_rate' => '5 / 7', 'parking_rate' => null]);

    expect(RateCorrections::apply())->toBe(3)
        ->and($slovakia->fresh()->reducedRates())->toBe([5.0, 19.0])
        ->and($slovakia->fresh()->super_reduced_rate)->toBeNull()
        ->and($finland->fresh()->reduced_rate)->toBe('10 / 13.5')
        ->and((float) $malta->fresh()->parking_rate)->toBe(12.0)
        ->and($malta->fresh()->reduced_rate)->toBe('5 / 7');
});

it('leaves rates that were already corrected and changes nothing the second time', function () {
    $romania = Country::factory()->create(['iso_code' => 'RO', 'reduced_rate' => '11']);
    $estonia = Country::factory()->create(['iso_code' => 'EE', 'reduced_rate' => '9']);

    expect(RateCorrections::apply())->toBe(1)
        ->and($romania->fresh()->reduced_rate)->toBe('11')
        ->and($estonia->fresh()->reduced_rate)->toBe('9 / 13')
        ->and(RateCorrections::apply())->toBe(0);
});

it('only corrects EU member states with a known snapshot value', function () {
    expect(array_diff(array_keys(RateCorrections::CHANGES), Country::EU_MEMBER_CODES))->toBe([]);

    foreach (RateCorrections::CHANGES as $iso => $columns) {
        foreach ($columns as $column => $change) {
            expect($column)->toBeIn(['reduced_rate', 'super_reduced_rate', 'parking_rate'])
                ->and(Country::parseRateList($change['from']))->not->toBe(Country::parseRateList($change['to']), "{$iso} {$column}");
        }
    }
});
