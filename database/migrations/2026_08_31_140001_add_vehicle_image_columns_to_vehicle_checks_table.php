<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicle_checks', function (Blueprint $table) {
            // The One Auto image-search URL expires after 7 days — well
            // inside a report's retention window — so the image bytes are
            // downloaded once and stored permanently ourselves here,
            // rather than storing that temporary URL.
            $table->string('vehicle_image_disk')->nullable()->after('purged_at');
            $table->string('vehicle_image_path')->nullable()->after('vehicle_image_disk');
        });
    }

    public function down(): void
    {
        Schema::table('vehicle_checks', function (Blueprint $table) {
            $table->dropColumn(['vehicle_image_disk', 'vehicle_image_path']);
        });
    }
};
