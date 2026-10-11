<?php

use App\Support\Vat\RateCorrections;
use Illuminate\Database\Migrations\Migration;

/**
 * Brings the reduced, super-reduced and parking rates of the January 2024 snapshot up to date with the European
 * Commission's figures. See RateCorrections for the list, its sources and its safeguards.
 */
return new class extends Migration
{
    public function up(): void
    {
        RateCorrections::apply();
    }

    public function down(): void
    {
        // Data corrections are not reverted: restoring superseded rates would reintroduce wrong data.
    }
};
