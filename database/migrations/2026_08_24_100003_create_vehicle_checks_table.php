<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicle_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vehicle_id')->constrained()->cascadeOnDelete();
            $table->string('type'); // check, plus, rebuild
            $table->string('status')->default('pending'); // pending, processing, completed, failed, refunded
            $table->string('stage')->nullable(); // current pipeline stage, for progress display
            $table->string('funding_source'); // free, subscription, credit, purchase

            // Back-references to payments/credit_transactions are intentionally
            // NOT foreign-key constrained: those tables are created after this
            // one and reference vehicle_checks themselves.
            $table->unsignedBigInteger('payment_id')->nullable();
            $table->unsignedBigInteger('credit_transaction_id')->nullable();

            $table->string('registration');
            $table->unsignedInteger('mileage')->nullable();
            $table->string('listing_url')->nullable();
            $table->string('auction_name')->nullable();
            $table->decimal('current_bid', 10, 2)->nullable();
            $table->decimal('asking_price', 10, 2)->nullable();
            $table->text('listing_description')->nullable();

            $table->text('failure_reason')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicle_checks');
    }
};
