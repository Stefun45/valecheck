<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicle_checks', function (Blueprint $table) {
            $table->timestamp('expires_at')->nullable()->after('completed_at');
            $table->timestamp('purged_at')->nullable()->after('expires_at');
        });
    }

    public function down(): void
    {
        Schema::table('vehicle_checks', function (Blueprint $table) {
            $table->dropColumn(['expires_at', 'purged_at']);
        });
    }
};
