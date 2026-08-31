<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicle_valuations', function (Blueprint $table) {
            // Tracks which underlying One Auto product produced this row —
            // 'ukvehicledata' (clean vehicles) or 'salvageguide' (write-off
            // vehicles) — so the report knows which section to render and
            // admin can see which product is actually being billed.
            $table->string('valuation_source')->nullable()->after('confidence');

            // UK Vehicle Data (clean vehicles only)
            $table->decimal('dealer_forecourt', 10, 2)->nullable()->after('valuation_source');
            $table->decimal('trade_average', 10, 2)->nullable()->after('dealer_forecourt');
            $table->decimal('trade_poor', 10, 2)->nullable()->after('trade_average');
            $table->decimal('private_average', 10, 2)->nullable()->after('trade_poor');
            $table->decimal('part_exchange', 10, 2)->nullable()->after('private_average');
            $table->decimal('auction_value', 10, 2)->nullable()->after('part_exchange');
            $table->decimal('list_price', 10, 2)->nullable()->after('auction_value');

            // SalvageGuide (write-off vehicles only) — a real market-
            // calibrated range, replacing the flat percentage guess that
            // still lives in SalvageValuationService as a fallback only.
            $table->decimal('category_adjusted_value_low', 10, 2)->nullable()->after('list_price');
            $table->decimal('category_adjusted_value_high', 10, 2)->nullable()->after('category_adjusted_value_low');
            $table->decimal('salvage_auction_bid_low', 10, 2)->nullable()->after('category_adjusted_value_high');
            $table->decimal('salvage_auction_bid_average', 10, 2)->nullable()->after('salvage_auction_bid_low');
            $table->decimal('salvage_auction_bid_high', 10, 2)->nullable()->after('salvage_auction_bid_average');
        });
    }

    public function down(): void
    {
        Schema::table('vehicle_valuations', function (Blueprint $table) {
            $table->dropColumn([
                'valuation_source', 'dealer_forecourt', 'trade_average', 'trade_poor',
                'private_average', 'part_exchange', 'auction_value', 'list_price',
                'category_adjusted_value_low', 'category_adjusted_value_high',
                'salvage_auction_bid_low', 'salvage_auction_bid_average', 'salvage_auction_bid_high',
            ]);
        });
    }
};
