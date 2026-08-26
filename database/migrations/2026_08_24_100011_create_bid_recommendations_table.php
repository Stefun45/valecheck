<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bid_recommendations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_check_id')->unique()->constrained()->cascadeOnDelete();

            $table->decimal('expected_resale_value', 10, 2);
            $table->decimal('total_repair_cost', 10, 2);
            $table->decimal('auction_fees', 10, 2);
            $table->decimal('transport_cost', 10, 2);
            $table->decimal('service_mot_allowance', 10, 2);
            $table->decimal('contingency', 10, 2);
            $table->decimal('required_margin', 10, 2);
            $table->decimal('maximum_acquisition_price', 10, 2);
            $table->decimal('recommended_bid', 10, 2);
            $table->decimal('absolute_maximum', 10, 2);

            // Filled in by a later stage of the processing pipeline (CalculateDealScore),
            // so these start nullable and are set once that stage completes.
            $table->unsignedTinyInteger('deal_score')->nullable();
            $table->string('recommendation')->nullable(); // buy, maybe, walk_away
            $table->text('score_explanation')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bid_recommendations');
    }
};
