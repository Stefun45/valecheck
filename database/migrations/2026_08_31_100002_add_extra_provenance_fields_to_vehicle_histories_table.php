<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicle_histories', function (Blueprint $table) {
            $table->integer('colour_changes')->nullable()->after('plate_changes');
            $table->boolean('was_exported')->nullable()->after('exported');
            $table->integer('vehicle_identity_checks')->nullable()->after('colour_changes');
            $table->integer('v5c_reissues')->nullable()->after('vehicle_identity_checks');
            $table->integer('previous_searches')->nullable()->after('v5c_reissues');
            $table->boolean('vrm_matches')->nullable()->after('previous_searches');
            $table->boolean('vin_matches')->nullable()->after('vrm_matches');
        });
    }

    public function down(): void
    {
        Schema::table('vehicle_histories', function (Blueprint $table) {
            $table->dropColumn([
                'colour_changes', 'was_exported', 'vehicle_identity_checks',
                'v5c_reissues', 'previous_searches', 'vrm_matches', 'vin_matches',
            ]);
        });
    }
};
