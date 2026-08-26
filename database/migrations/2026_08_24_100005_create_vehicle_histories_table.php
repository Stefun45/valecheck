<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_check_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('write_off_category')->nullable(); // A, B, S, N
            $table->date('write_off_date')->nullable();
            $table->boolean('finance_marker')->default(false);
            $table->boolean('stolen_marker')->default(false);
            $table->boolean('scrapped_marker')->default(false);
            $table->boolean('imported')->default(false);
            $table->boolean('exported')->default(false);
            $table->unsignedTinyInteger('previous_keepers')->nullable();
            $table->unsignedTinyInteger('plate_changes')->nullable();
            $table->boolean('mileage_anomaly')->default(false);
            $table->json('mot_history')->nullable();
            $table->json('keeper_history')->nullable();
            $table->json('raw_provider_data')->nullable();
            $table->string('confidence')->default('high'); // low, medium, high
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_histories');
    }
};
