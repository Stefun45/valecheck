<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('damage_findings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('damage_analysis_id')->constrained()->cascadeOnDelete();
            $table->string('component');
            $table->string('condition'); // ok, damaged, missing, unknown
            $table->string('severity')->nullable(); // low, medium, high
            $table->string('recommended_action')->nullable(); // none, repair, replace, inspect
            $table->decimal('confidence', 3, 2)->default(0.5);
            $table->text('explanation')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('damage_findings');
    }
};
