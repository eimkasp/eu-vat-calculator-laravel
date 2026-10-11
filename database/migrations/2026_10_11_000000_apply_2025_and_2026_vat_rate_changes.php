<?php

use App\Support\Vat\RateCorrections;
use Illuminate\Database\Migrations\Migration;

/**
 * Brings the reduced, super-reduced and parking rates that changed in 2025 and 2026 up to date, from the European
 * Commission's TEDB. See RateCorrections for the list and its safeguards.
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
