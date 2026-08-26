<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('repair_estimates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_check_id')->unique()->constrained()->cascadeOnDelete();
            $table->decimal('low_estimate', 10, 2);
            $table->decimal('expected_estimate', 10, 2);
            $table->decimal('high_estimate', 10, 2);
            $table->json('assumptions_snapshot')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('repair_estimates');
    }
};
