<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('vehicle_checks', function (Blueprint $table) {
            // A short random public identifier for URLs (11 alphanumeric
            // characters, like a YouTube video ID) — the sequential `id`
            // column stays the real primary key everywhere internally,
            // this is only ever used in customer-facing links, so a
            // sequential number never reveals how many checks have run.
            $table->string('public_id', 20)->nullable()->unique()->after('id');
        });

        // The raw query builder, not the Eloquent model — public_id is
        // deliberately absent from VehicleCheck's Fillable list (it's
        // never meant to be settable from outside the creating event), so
        // $check->update(['public_id' => ...]) would be silently
        // discarded by the same mass-assignment guard and leave every
        // pre-existing row null. DB::table() has no concept of fillable.
        DB::table('vehicle_checks')->whereNull('public_id')->orderBy('id')->pluck('id')
            ->each(fn (int $id) => DB::table('vehicle_checks')->where('id', $id)->update(['public_id' => Str::random(11)]));
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vehicle_checks', function (Blueprint $table) {
            $table->dropColumn('public_id');
        });
    }
};
