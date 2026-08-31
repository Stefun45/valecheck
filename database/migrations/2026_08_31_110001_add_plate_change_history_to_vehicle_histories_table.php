<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicle_histories', function (Blueprint $table) {
            $table->json('plate_change_history')->nullable()->after('plate_changes');
        });
    }

    public function down(): void
    {
        Schema::table('vehicle_histories', function (Blueprint $table) {
            $table->dropColumn('plate_change_history');
        });
    }
};
