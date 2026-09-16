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
            // Already fetched from the same MOT/Tax response used for the
            // free preview (dvsa_data.dvsa_vehicle_Data.first_registration_date)
            // but previously discarded before reaching the paid report —
            // see OneAutoVehicleDataProvider.
            $table->date('first_registration_date')->nullable()->after('write_off_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vehicle_histories', function (Blueprint $table) {
            $table->dropColumn('first_registration_date');
        });
    }
};
