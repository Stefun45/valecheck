<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Replaces the original single-row/percentage design with one row
     * per product type, each carrying its own explicit discounted price
     * (not a percentage) - lets the admin set (or not set) a real "was
     * £X now £Y" price for each plan individually, and switch each on or
     * off independently. See App\Models\SitePromotion.
     */
    public function up(): void
    {
        Schema::table('site_promotions', function (Blueprint $table) {
            $table->string('type')->nullable()->after('id');
            $table->decimal('discounted_gross', 8, 2)->nullable()->after('type');
        });

        DB::table('site_promotions')->truncate();

        $now = now();
        DB::table('site_promotions')->insert(
            collect(['check', 'plus', 'rebuild', 'plus_upgrade'])->map(fn (string $type) => [
                'type' => $type,
                'is_active' => false,
                'discounted_gross' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ])->all()
        );

        Schema::table('site_promotions', function (Blueprint $table) {
            $table->string('type')->nullable(false)->unique()->change();
            $table->dropColumn(['percentage', 'label']);
        });
    }

    public function down(): void
    {
        Schema::table('site_promotions', function (Blueprint $table) {
            $table->dropUnique(['type']);
            $table->dropColumn(['type', 'discounted_gross']);
            $table->decimal('percentage', 5, 2)->default(0);
            $table->string('label')->default('Launch Offer');
        });

        DB::table('site_promotions')->truncate();
        DB::table('site_promotions')->insert([
            'is_active' => false,
            'percentage' => 0,
            'label' => 'Launch Offer',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
};
