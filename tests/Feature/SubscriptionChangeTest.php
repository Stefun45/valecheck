<?php

namespace Tests\Feature;

use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\Credits\CreditLedgerService;
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

    private function plan(int $sortOrder, string $group = 'pro'): SubscriptionPlan
    {
        return SubscriptionPlan::create([
            'name' => "Plan {$sortOrder}",
            'group' => $group,
            'stripe_price_id' => "price_{$sortOrder}",
            'monthly_net' => 99.00,
            'monthly_credits' => $sortOrder * 25,
            'additional_credit_net' => 4.99,
            'is_active' => true,
            'sort_order' => $sortOrder,
        ]);
    }

    private function existingStripeSubscription(User $user): void
    {
        Subscription::create([
            'user_id' => $user->id,
            'type' => 'default',
            'stripe_id' => 'sub_existing',
            'stripe_status' => 'active',
            'stripe_price' => 'price_old',
            'quantity' => 1,
        ]);
    }

    /**
     * Real Stripe calls can't run in this environment (no live key), so
     * this swaps in a recording fake for exactly the two methods that
     * matter here - which one gets called, and with what proration - and
     * throws to make that observable, rather than asserting on a real
     * Checkout Session or subscription update.
     */
    private function fakeCheckoutService(): object
    {
        return new class extends StripeCheckoutService
        {
            public function __construct() {}

            public function checkoutForSubscription($user, $plan): Checkout
            {
                throw new RuntimeException("checkout called with {$plan->name}");
            }

            public function swapSubscription($user, $plan, bool $prorate = true): void
            {
                throw new RuntimeException("swap called with {$plan->name} prorate=".($prorate ? 'true' : 'false'));
            }
        };
    }

    public function test_subscribing_for_the_first_time_goes_through_checkout_not_swap(): void
    {
        $plan = $this->plan(1);
        $user = User::factory()->create();
        $this->actingAs($user);
        $this->app->instance(StripeCheckoutService::class, $this->fakeCheckoutService());

        $this->withoutExceptionHandling();
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("checkout called with {$plan->name}");

        $this->post(route('billing.subscribe'), ['plan_id' => $plan->id]);
    }

    public function test_moving_to_a_higher_plan_swaps_immediately_with_proration(): void
    {
        $lower = $this->plan(1);
        $higher = $this->plan(2);
        $user = User::factory()->create();
        $this->existingStripeSubscription($user);
        app(CreditLedgerService::class)->grantSubscriptionCredits($user, $lower, $lower->monthly_credits, now()->addDays(15));
        $this->actingAs($user);
        $this->app->instance(StripeCheckoutService::class, $this->fakeCheckoutService());

        $this->withoutExceptionHandling();
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("swap called with {$higher->name} prorate=true");

        $this->post(route('billing.subscribe'), ['plan_id' => $higher->id]);
    }

    public function test_moving_to_a_lower_plan_never_touches_stripe_immediately(): void
    {
        $lower = $this->plan(1);
        $higher = $this->plan(2);
        $user = User::factory()->create();
        $this->existingStripeSubscription($user);
        app(CreditLedgerService::class)->grantSubscriptionCredits($user, $higher, $higher->monthly_credits, now()->addDays(15));
        $this->actingAs($user);
        // Not faked - if a downgrade touched Stripe, the real (unconfigured)
        // call would throw and fail this test, proving it never gets there.
        $this->app->instance(StripeCheckoutService::class, $this->fakeCheckoutService());

        $response = $this->post(route('billing.subscribe'), ['plan_id' => $lower->id]);

        $response->assertRedirect(route('dashboard'));
        $this->assertSame($lower->id, $user->fresh()->pending_plan_id);
    }
}
