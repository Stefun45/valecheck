<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('provider_lookup_logs', function (Blueprint $table) {
            // The cost actually in effect at the moment this specific call
            // was made, snapshotted by OneAutoClient::log() from
            // provider_endpoint_costs — never recomputed later, so editing
            // a cost going forward can't rewrite historical spend figures.
            $table->decimal('cost_net', 8, 4)->nullable()->after('error_message');
        });

        // Rows logged before this column existed have no snapshot to fall
        // back on — backfill them with the flat rate that was actually in
        // effect at the time (the same figure AdminMetricsService was
        // already applying to them), so historical totals don't change.
        $legacyFlatCost = (float) config('valecheck.vehicle_data.oneauto.cost_per_lookup_net', 0);

        DB::table('provider_lookup_logs')->whereNull('cost_net')->update(['cost_net' => $legacyFlatCost]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('provider_lookup_logs', function (Blueprint $table) {
            $table->dropColumn('cost_net');
        });
    }
};
