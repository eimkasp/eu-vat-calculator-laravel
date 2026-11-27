<?php

use App\Support\Vat\RateCorrections;
use Illuminate\Database\Migrations\Migration;

/**
 * Applies the corrections list again for Ireland, whose super-reduced and parking rates the European Commission no
 * longer lists. The list is idempotent and leaves hand-corrected values alone; see RateCorrections.
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
