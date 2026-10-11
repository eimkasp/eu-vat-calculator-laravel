<?php

namespace Database\Seeders;

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

        // The country list is a January 2024 snapshot; the rate history is current, so it decides the standard rate.
        Artisan::call('vat:verify', ['--fix' => true]);
    }
}
