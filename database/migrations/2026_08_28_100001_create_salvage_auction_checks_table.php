<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salvage_auction_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_check_id')->unique()->constrained()->cascadeOnDelete();
            $table->boolean('record_found')->default(false);
            $table->json('records')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salvage_auction_checks');
    }
};
