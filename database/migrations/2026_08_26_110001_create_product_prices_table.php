<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_prices', function (Blueprint $table) {
            $table->id();
            $table->string('type')->unique(); // check, plus, rebuild
            $table->decimal('gross', 10, 2);
            $table->timestamps();
        });

        // Seeded from the values that were previously hard-coded in
        // config/valecheck.php, so this migration doesn't change what
        // anyone is currently being charged — the admin price-edit screen
        // is what changes them from here on.
        $now = now();
        DB::table('product_prices')->insert([
            ['type' => 'check', 'gross' => config('valecheck.pricing.check.gross', 8.99), 'created_at' => $now, 'updated_at' => $now],
            ['type' => 'plus', 'gross' => config('valecheck.pricing.plus.gross', 11.99), 'created_at' => $now, 'updated_at' => $now],
            ['type' => 'rebuild', 'gross' => config('valecheck.pricing.rebuild.gross', 14.99), 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('product_prices');
    }
};
