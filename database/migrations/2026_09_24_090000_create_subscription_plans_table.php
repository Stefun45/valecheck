<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            // 'pro' or 'dealer' - not just a label, this is what
            // User::hasVerifiedTradeAccess() gates trade-restricted report
            // content on. Pro needs no verification, Dealer does.
            $table->string('group');
            $table->string('stripe_price_id')->nullable();
            // Ex-VAT, per the commercial spec - PricingService::breakdownFromNet()
            // computes the VAT-inclusive gross for display/charging.
            $table->decimal('monthly_net', 8, 2);
            $table->unsignedInteger('monthly_credits');
            $table->decimal('additional_credit_net', 8, 2);
            $table->boolean('is_active')->default(true);
            // Defines both display order and upgrade/downgrade direction -
            // moving to a higher sort_order is an upgrade, lower is a
            // downgrade. No separate relationship table needed.
            $table->unsignedInteger('sort_order');
            $table->timestamps();
        });

        // Launch lineup - see the commercial spec. stripe_price_id is left
        // null until the real Stripe recurring prices exist; checkout is
        // already guarded (StripeCheckoutService) to reject a plan with no
        // price configured rather than silently failing.
        DB::table('subscription_plans')->insert([
            ['name' => 'Pro 25', 'group' => 'pro', 'monthly_net' => 99.00, 'monthly_credits' => 25, 'additional_credit_net' => 4.99, 'sort_order' => 1, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Pro 50', 'group' => 'pro', 'monthly_net' => 179.00, 'monthly_credits' => 50, 'additional_credit_net' => 4.49, 'sort_order' => 2, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Dealer 100', 'group' => 'dealer', 'monthly_net' => 299.00, 'monthly_credits' => 100, 'additional_credit_net' => 3.99, 'sort_order' => 3, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Dealer 250', 'group' => 'dealer', 'monthly_net' => 699.00, 'monthly_credits' => 250, 'additional_credit_net' => 3.79, 'sort_order' => 4, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Dealer 500', 'group' => 'dealer', 'monthly_net' => 1299.00, 'monthly_credits' => 500, 'additional_credit_net' => 3.49, 'sort_order' => 5, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Dealer 1,000', 'group' => 'dealer', 'monthly_net' => 2399.00, 'monthly_credits' => 1000, 'additional_credit_net' => 3.29, 'sort_order' => 6, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_plans');
    }
};
