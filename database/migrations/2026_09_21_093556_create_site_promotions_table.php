<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // A single row, toggled on/off from the admin area without a
        // deploy - a temporary launch-period discount, distinct from the
        // customer-typed discount-code system. See App\Models\SitePromotion.
        Schema::create('site_promotions', function (Blueprint $table) {
            $table->id();
            $table->boolean('is_active')->default(false);
            $table->decimal('percentage', 5, 2)->default(0);
            $table->string('label')->default('Launch Offer');
            $table->timestamps();
        });

        DB::table('site_promotions')->insert([
            'is_active' => false,
            'percentage' => 0,
            'label' => 'Launch Offer',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('site_promotions');
    }
};
