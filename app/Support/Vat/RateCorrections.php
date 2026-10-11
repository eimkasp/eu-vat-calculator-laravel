<?php

namespace App\Support\Vat;

use App\Models\Country;
use Illuminate\Support\Facades\DB;

/**
 * Rates that changed after the January 2024 country snapshot (data/eu-vat-2024-jan.csv), as published in the
 * European Commission's Taxes in Europe Database (TEDB) and confirmed by the Commission's Your Europe rates table.
 * Where the two disagree (TEDB lists no parking rate for Austria or Ireland, and a parking rate for Greece), the
 * published table wins. The open rate history the site syncs covers standard rates only, so these columns would
 * otherwise keep the snapshot values.
 *
 * A value is replaced only while the stored value still matches the snapshot, so a rate an administrator has
 * already corrected is left alone and applying the list twice changes nothing.
 */
final class RateCorrections
{
    /**
     * @var array<string, array<'reduced_rate'|'super_reduced_rate'|'parking_rate', array{from: ?string, to: ?string}>>
     */
    public const CHANGES = [
        // 4.9% on basic foodstuffs from 1 July 2026.
        'AT' => ['super_reduced_rate' => ['from' => null, 'to' => '4.9']],
        // 3% on books, newspapers, medical equipment and other supplies, in force before 2024.
        'CY' => ['super_reduced_rate' => ['from' => null, 'to' => '3']],
        // 13% on accommodation from 1 January 2025; the 5% press rate became 9% on the same day.
        'EE' => ['reduced_rate' => ['from' => '9', 'to' => '9 / 13']],
        // 14% lowered to 13.5% from 1 January 2026.
        'FI' => ['reduced_rate' => ['from' => '10 / 14', 'to' => '10 / 13.5']],
        // 4% on works that remove barriers for people with disabilities, from 1 November 2023 (Law 5057/2023).
        'GR' => ['super_reduced_rate' => ['from' => null, 'to' => '4']],
        // 9% raised to 12% from 1 January 2026.
        'LT' => ['reduced_rate' => ['from' => '5 / 9', 'to' => '5 / 12']],
        // 12% parking rate, in force before 2024.
        'MT' => ['parking_rate' => ['from' => null, 'to' => '12']],
        // 5% and 9% merged into 11% from 1 August 2025.
        'RO' => ['reduced_rate' => ['from' => '5 / 9', 'to' => '11']],
        // 5% and 19% from 1 January 2025, replacing 10%; 5% is a reduced rate, not a super-reduced one.
        'SK' => [
            'reduced_rate' => ['from' => '10', 'to' => '5 / 19'],
            'super_reduced_rate' => ['from' => '5', 'to' => null],
        ],
    ];

    /**
     * @return int The number of countries updated.
     */
    public static function apply(): int
    {
        $updated = 0;

        foreach (self::CHANGES as $iso => $columns) {
            $country = DB::table('countries')->where('iso_code', $iso)->first(['id', ...array_keys($columns)]);

            if (! $country) {
                continue;
            }

            $values = [];

            foreach ($columns as $column => $change) {
                if (Country::parseRateList($country->{$column}) === Country::parseRateList($change['from'])) {
                    $values[$column] = $change['to'];
                }
            }

            if ($values !== []) {
                DB::table('countries')->where('id', $country->id)->update($values + ['updated_at' => now()]);
                $updated++;
            }
        }

        return $updated;
    }
}
