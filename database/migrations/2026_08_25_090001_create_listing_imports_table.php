<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('listing_imports', function (Blueprint $table) {
            $table->id();
            $table->string('url');
            $table->string('url_hash')->index();
            $table->string('domain');
            $table->string('provider'); // generic, ebay, autotrader, copart
            $table->string('status')->default('pending'); // pending, success, partial, failed, blocked
            $table->json('data')->nullable(); // field => ['value' => ..., 'found' => bool]
            $table->unsignedSmallInteger('image_count_found')->default(0);
            $table->boolean('images_capped')->default(false);
            $table->unsignedSmallInteger('http_status')->nullable();
            $table->text('error_message')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('listing_imports');
    }
};
