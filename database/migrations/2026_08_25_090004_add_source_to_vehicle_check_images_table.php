<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicle_check_images', function (Blueprint $table) {
            $table->string('source')->default('uploaded')->after('position'); // uploaded, imported
        });
    }

    public function down(): void
    {
        Schema::table('vehicle_check_images', function (Blueprint $table) {
            $table->dropColumn('source');
        });
    }
};
