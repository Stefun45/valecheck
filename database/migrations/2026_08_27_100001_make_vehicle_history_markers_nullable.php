<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * These were plain booleans defaulting to false, which is exactly how a
 * provider silently omitting its provenance data got misread as "clean" —
 * see the VehicleMatic-to-One-Auto migration. Recreated as nullable with
 * no default: null now means "provider didn't return this", distinct from
 * false ("provider checked, found nothing").
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicle_histories', function (Blueprint $table) {
            $table->dropColumn(['finance_marker', 'stolen_marker', 'scrapped_marker', 'imported', 'exported', 'mileage_anomaly']);
        });

        Schema::table('vehicle_histories', function (Blueprint $table) {
            $table->boolean('finance_marker')->nullable()->after('write_off_date');
            $table->boolean('stolen_marker')->nullable()->after('finance_marker');
            $table->boolean('scrapped_marker')->nullable()->after('stolen_marker');
            $table->boolean('imported')->nullable()->after('scrapped_marker');
            $table->boolean('exported')->nullable()->after('imported');
            $table->boolean('mileage_anomaly')->nullable()->after('plate_changes');
        });
    }

    public function down(): void
    {
        Schema::table('vehicle_histories', function (Blueprint $table) {
            $table->dropColumn(['finance_marker', 'stolen_marker', 'scrapped_marker', 'imported', 'exported', 'mileage_anomaly']);
        });

        Schema::table('vehicle_histories', function (Blueprint $table) {
            $table->boolean('finance_marker')->default(false)->after('write_off_date');
            $table->boolean('stolen_marker')->default(false)->after('finance_marker');
            $table->boolean('scrapped_marker')->default(false)->after('stolen_marker');
            $table->boolean('imported')->default(false)->after('scrapped_marker');
            $table->boolean('exported')->default(false)->after('imported');
            $table->boolean('mileage_anomaly')->default(false)->after('plate_changes');
        });
    }
};
