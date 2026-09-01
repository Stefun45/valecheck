<?php

use App\Models\VehicleCheck;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
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

        VehicleCheck::whereNull('public_id')
            ->orderBy('id')
            ->each(fn (VehicleCheck $check) => $check->update(['public_id' => Str::random(11)]));
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
