<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('vehicle_histories', function (Blueprint $table) {
            // Already fetched from the same AutoCheck response used for
            // every other identity field on this row (top-level
            // result.co2_gkm) but previously discarded before reaching
            // the report - see OneAutoVehicleDataProvider. Purely
            // informational here (One Auto's own dedicated tax endpoint
            // already computes the real VED figure using CO2 itself -
            // see VehicleTaxCost - this column is never used to derive a
            // tax figure of our own).
            $table->integer('co2_gkm')->nullable()->after('first_registration_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vehicle_histories', function (Blueprint $table) {
            $table->dropColumn('co2_gkm');
        });
    }
};
