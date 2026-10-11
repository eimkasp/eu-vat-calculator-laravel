<?php

namespace Database\Seeders;

use App\Support\Vat\RateCorrections;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(CountriesTableSeeder::class);
        $this->call(VatRateSeeder::class);

        // The country list is a January 2024 snapshot: the rate history decides the standard rate, and the
        // Commission's later reduced, super-reduced and parking rates replace the snapshot's.
        Artisan::call('vat:verify', ['--fix' => true]);
        RateCorrections::apply();
    }
}
