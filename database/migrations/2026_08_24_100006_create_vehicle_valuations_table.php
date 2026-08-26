<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_valuations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_check_id')->unique()->constrained()->cascadeOnDelete();
            $table->decimal('clean_value', 10, 2)->nullable();
            $table->decimal('trade_value', 10, 2)->nullable();
            $table->decimal('retail_value', 10, 2)->nullable();
            $table->decimal('private_value', 10, 2)->nullable();
            $table->decimal('salvage_adjusted_value', 10, 2)->nullable();
            $table->string('write_off_category_applied')->nullable();
            $table->decimal('discount_applied', 5, 4)->nullable();
            $table->json('comparables')->nullable();
            $table->string('confidence')->default('medium'); // low, medium, high
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_valuations');
    }
};
