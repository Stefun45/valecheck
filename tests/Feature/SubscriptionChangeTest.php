<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Payments\StripeCheckoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Cashier\Checkout;
use Laravel\Cashier\Subscription;
use RuntimeException;
use Tests\TestCase;

class SubscriptionChangeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['valecheck.subscriptions_enabled' => true, 'cashier.secret' => 'sk_test_fake']);
    }

    /**
     * Real Stripe calls can't run in this environment (no live key), so
     * this swaps in a recording fake for exactly the two methods that
     * matter here - which one gets called - rather than asserting on a
     * real Checkout Session or subscription update.
     */
    private function fakeCheckoutService(): object
    {
        return new class extends StripeCheckoutService
        {
            public function __construct() {}

            public function checkoutForSubscription($user, string $plan): Checkout
            {
                throw new RuntimeException("checkout called with {$plan}");
            }

            public function swapSubscription($user, string $plan): void
            {
                throw new RuntimeException("swap called with {$plan}");
            }
        };
    }

    public function test_subscribing_for_the_first_time_goes_through_checkout_not_swap(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $this->app->instance(StripeCheckoutService::class, $this->fakeCheckoutService());

        $this->withoutExceptionHandling();
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('checkout called with dealer');

        $this->post(route('billing.subscribe'), ['plan' => 'dealer']);
    }

    public function test_subscribing_while_already_subscribed_swaps_the_plan_instead_of_a_second_checkout(): void
    {
        $user = User::factory()->create();
        Subscription::create([
            'user_id' => $user->id,
            'type' => 'default',
            'stripe_id' => 'sub_existing',
            'stripe_status' => 'active',
            'stripe_price' => 'price_old',
            'quantity' => 1,
        ]);
        $this->actingAs($user);
        $this->app->instance(StripeCheckoutService::class, $this->fakeCheckoutService());

        $this->withoutExceptionHandling();
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('swap called with pro');

        $this->post(route('billing.subscribe'), ['plan' => 'pro']);
    }
}
