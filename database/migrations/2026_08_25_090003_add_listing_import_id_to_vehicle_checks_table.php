<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicle_checks', function (Blueprint $table) {
            $table->foreignId('listing_import_id')->nullable()->after('vehicle_id')->constrained()->nullOnDelete();
            $table->json('listing_data_sources')->nullable()->after('listing_description');
        });
    }

    public function down(): void
    {
        Schema::table('vehicle_checks', function (Blueprint $table) {
            $table->dropConstrainedForeignId('listing_import_id');
            $table->dropColumn('listing_data_sources');
        });
    }
};
