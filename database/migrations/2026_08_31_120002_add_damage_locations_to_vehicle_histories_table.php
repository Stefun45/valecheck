<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicle_histories', function (Blueprint $table) {
            $table->json('damage_locations')->nullable()->after('write_off_date');
        });
    }

    public function down(): void
    {
        Schema::table('vehicle_histories', function (Blueprint $table) {
            $table->dropColumn('damage_locations');
        });
    }
};
