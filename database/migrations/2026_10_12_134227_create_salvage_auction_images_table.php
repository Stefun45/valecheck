<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salvage_auction_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('salvage_auction_check_id')->constrained()->cascadeOnDelete();
            // Which entry in the parent's records JSON array this image
            // belongs to - a vehicle can rarely have more than one salvage
            // record, so this is almost always 0.
            $table->unsignedInteger('record_index')->default(0);
            // The original One Auto third-party URL, kept for reference/
            // debugging only - never re-fetched from, since it expires
            // after ~12 hours and the bytes are stored permanently below.
            $table->text('source_url');
            $table->string('disk');
            $table->string('path');
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salvage_auction_images');
    }
};
