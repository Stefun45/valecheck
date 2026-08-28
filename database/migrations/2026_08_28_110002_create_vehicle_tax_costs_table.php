<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_tax_costs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_check_id')->unique()->constrained()->cascadeOnDelete();
            $table->boolean('available')->default(false);
            $table->decimal('annual_rate', 8, 2)->nullable();
            $table->decimal('six_month_rate', 8, 2)->nullable();
            $table->string('tax_class')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_tax_costs');
    }
};
