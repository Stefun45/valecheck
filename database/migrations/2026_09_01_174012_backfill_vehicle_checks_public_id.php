<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Fixes any environment where 2026_09_01_172502_add_public_id_to_vehicle_checks_table
 * already ran with its original, broken backfill — that version called
 * $check->update(['public_id' => ...]), which the Eloquent model silently
 * discarded (public_id is deliberately not Fillable), leaving every
 * pre-existing row null. This repeats the backfill via the raw query
 * builder, which isn't affected by that guard. A no-op wherever the fixed
 * version already ran cleanly, since there'll be nothing left to update.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('vehicle_checks')->whereNull('public_id')->orderBy('id')->pluck('id')
            ->each(fn (int $id) => DB::table('vehicle_checks')->where('id', $id)->update(['public_id' => Str::random(11)]));
    }

    public function down(): void
    {
        // Nothing to revert — this only fills in previously-null values.
    }
};
