<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\Payments\StripeCheckoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class StripeCheckoutServiceSubscriptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_checkout_for_subscription_rejects_a_plan_with_no_stripe_price_configured(): void
    {
        config(['valecheck.pricing.subscriptions.trader.stripe_price' => null]);
        $user = User::factory()->create();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('STRIPE_PRICE_TRADER');

        app(StripeCheckoutService::class)->checkoutForSubscription($user, 'trader');
    }

    public function test_swap_subscription_rejects_a_plan_with_no_stripe_price_configured(): void
    {
        config(['valecheck.pricing.subscriptions.dealer.stripe_price' => null]);
        $user = User::factory()->create();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('STRIPE_PRICE_DEALER');

        app(StripeCheckoutService::class)->swapSubscription($user, 'dealer');
    }
}
