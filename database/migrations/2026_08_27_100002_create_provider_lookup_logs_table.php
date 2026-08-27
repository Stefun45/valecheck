<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('provider_lookup_logs', function (Blueprint $table) {
            $table->id();
            $table->string('provider'); // 'oneauto'
            $table->string('endpoint'); // e.g. 'experian/autocheck'
            $table->string('registration');
            $table->foreignId('vehicle_check_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status'); // 'success', 'failed'
            $table->unsignedSmallInteger('http_status')->nullable();
            $table->string('error_message')->nullable(); // server-side diagnostics only, never shown to customers
            $table->timestamps();

            $table->index(['provider', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('provider_lookup_logs');
    }
};
