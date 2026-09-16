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
        Schema::table('vehicle_checks', function (Blueprint $table) {
            // A demonstration check shown publicly on /sample-report — not
            // a real customer's data, and excluded from every business
            // metric in AdminMetricsService so it can never distort real
            // revenue/usage figures.
            $table->boolean('is_sample')->default(false)->after('funding_source');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vehicle_checks', function (Blueprint $table) {
            $table->dropColumn('is_sample');
        });
    }
};
