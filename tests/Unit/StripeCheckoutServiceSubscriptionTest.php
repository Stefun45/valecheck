<?php

namespace Tests\Unit;

use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\Payments\StripeCheckoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class StripeCheckoutServiceSubscriptionTest extends TestCase
{
    use RefreshDatabase;

    private function planWithNoPrice(): SubscriptionPlan
    {
        return SubscriptionPlan::create([
            'name' => 'Unpriced Plan',
            'group' => 'pro',
            'stripe_price_id' => null,
            'monthly_net' => 99.00,
            'monthly_credits' => 25,
            'additional_credit_net' => 4.99,
            'is_active' => true,
            'sort_order' => 1,
        ]);
    }

    public function test_checkout_for_subscription_rejects_a_plan_with_no_stripe_price_configured(): void
    {
        $plan = $this->planWithNoPrice();
        $user = User::factory()->create();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($plan->name);

        app(StripeCheckoutService::class)->checkoutForSubscription($user, $plan);
    }

    public function test_swap_subscription_rejects_a_plan_with_no_stripe_price_configured(): void
    {
        $plan = $this->planWithNoPrice();
        $user = User::factory()->create();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($plan->name);

        app(StripeCheckoutService::class)->swapSubscription($user, $plan);
    }
}
