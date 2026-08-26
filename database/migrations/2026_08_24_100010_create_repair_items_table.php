<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('repair_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('repair_estimate_id')->constrained()->cascadeOnDelete();
            $table->string('component');
            $table->string('action'); // repair, replace
            $table->decimal('parts_cost', 10, 2)->default(0);
            $table->decimal('labour_hours', 5, 2)->default(0);
            $table->decimal('labour_cost', 10, 2)->default(0);
            $table->decimal('paint_cost', 10, 2)->default(0);
            $table->decimal('total_cost', 10, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('repair_items');
    }
};
