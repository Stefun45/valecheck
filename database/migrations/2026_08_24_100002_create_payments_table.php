<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type'); // check, plus, rebuild, credit_pack, subscription
            $table->string('description');
            $table->decimal('gross', 10, 2);
            $table->decimal('net', 10, 2);
            $table->decimal('vat', 10, 2);
            $table->decimal('vat_rate', 5, 4);
            $table->string('currency', 3)->default('GBP');
            $table->string('stripe_checkout_session_id')->nullable()->index();
            $table->string('stripe_payment_intent_id')->nullable()->index();
            $table->string('status')->default('pending'); // pending, paid, refunded, failed
            $table->timestamp('refunded_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
