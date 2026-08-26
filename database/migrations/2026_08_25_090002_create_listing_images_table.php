<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('listing_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('listing_import_id')->constrained()->cascadeOnDelete();
            $table->string('source_url');
            $table->string('disk')->nullable();
            $table->string('path')->nullable();
            $table->string('hash')->nullable();
            $table->unsignedSmallInteger('position')->default(0);
            $table->string('status')->default('pending'); // pending, downloaded, failed, skipped_duplicate, skipped_over_limit
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('listing_images');
    }
};
