<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_check_id')->nullable()->constrained()->nullOnDelete();
            $table->string('provider'); // anthropic, openai
            $table->string('model');
            $table->string('stage'); // image_analysis, explanation
            $table->unsignedInteger('input_tokens')->nullable();
            $table->unsignedInteger('output_tokens')->nullable();
            $table->unsignedTinyInteger('image_count')->default(0);
            $table->decimal('estimated_cost', 8, 4)->nullable();
            $table->decimal('actual_cost', 8, 4)->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->boolean('success')->default(true);
            $table->text('error_message')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_usages');
    }
};
