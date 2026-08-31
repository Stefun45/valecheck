<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicle_histories', function (Blueprint $table) {
            // Nullable, not defaulted to false — matches finance_marker/
            // stolen_marker's tri-state honesty: null means the provider
            // didn't return this section, not "checked and clean".
            $table->boolean('high_risk_marker')->nullable()->after('scrapped_marker');
        });
    }

    public function down(): void
    {
        Schema::table('vehicle_histories', function (Blueprint $table) {
            $table->dropColumn('high_risk_marker');
        });
    }
};
