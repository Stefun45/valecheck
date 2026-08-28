<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        // Deliberately not (plus - check) — that would round to £3.00, but
        // a second Stripe transaction fee on top of the original purchase's
        // fee means the true marginal cost is higher than the raw
        // difference, so this is priced slightly above it on purpose.
        DB::table('product_prices')->insert([
            'type' => 'plus_upgrade',
            'gross' => config('valecheck.pricing.plus_upgrade.gross', 3.50),
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function down(): void
    {
        DB::table('product_prices')->where('type', 'plus_upgrade')->delete();
    }
};
