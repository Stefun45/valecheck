<?php

namespace Tests\Unit;

use App\Models\CreditTransaction;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\Credits\CreditLedgerService;
use App\Services\Payments\StripeCheckoutService;
use App\Services\Subscriptions\SubscriptionPlanChangeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionPlanChangeServiceTest extends TestCase
{
    use RefreshDatabase;

    private function plan(string $name, int $credits, int $sortOrder): SubscriptionPlan
    {
        return SubscriptionPlan::create([
            'name' => $name,
            'group' => 'pro',
            'stripe_price_id' => "price_{$sortOrder}",
            'monthly_net' => 99.00,
            'monthly_credits' => $credits,
            'additional_credit_net' => 4.99,
            'is_active' => true,
            'sort_order' => $sortOrder,
        ]);
    }

    /**
     * Matches the commercial spec's own worked example: Pro 25 -> Pro 50
     * mid-period should grant the incremental 25 credits, not reset to a
     * fresh 50 (the customer already has 25 for this period; the upgrade
     * tops that up to 50, it doesn't restart it).
     */
    public function test_an_upgrade_grants_only_the_incremental_credit_difference(): void
    {
        $lower = $this->plan('Pro 25', 25, 1);
        $higher = $this->plan('Pro 50', 50, 2);
        $user = User::factory()->create();
        $expiresAt = now()->addDays(12);
        app(CreditLedgerService::class)->grantSubscriptionCredits($user, $lower, 25, $expiresAt);

        $fake = new class extends StripeCheckoutService
        {
            public array $calls = [];

            public function __construct() {}

            public function swapSubscription($user, $plan, bool $prorate = true): void
            {
                $this->calls[] = ['plan_id' => $plan->id, 'prorate' => $prorate];
            }
        };
        $this->app->instance(StripeCheckoutService::class, $fake);

        app(SubscriptionPlanChangeService::class)->upgrade($user, $higher);

        $this->assertSame([['plan_id' => $higher->id, 'prorate' => true]], $fake->calls);
        $this->assertSame(50, app(CreditLedgerService::class)->balance($user, 'plus'));

        // The bonus expires with the SAME period the original grant was
        // for, not a fresh one - an upgrade tops up the current period,
        // it doesn't extend it.
        $bonus = CreditTransaction::where('user_id', $user->id)
            ->where('subscription_plan_id', $higher->id)
            ->firstOrFail();
        $this->assertSame($expiresAt->toDateTimeString(), $bonus->expires_at->toDateTimeString());
        $this->assertSame(25, $bonus->amount);
    }

    public function test_a_downgrade_request_only_sets_pending_plan_and_never_touches_stripe(): void
    {
        $higher = $this->plan('Dealer 250', 250, 2);
        $lower = $this->plan('Dealer 100', 100, 1);
        $user = User::factory()->create();
        app(CreditLedgerService::class)->grantSubscriptionCredits($user, $higher, 250, now()->addDays(12));

        $fake = new class extends StripeCheckoutService
        {
            public function __construct() {}

            public function swapSubscription($user, $plan, bool $prorate = true): void
            {
                throw new \RuntimeException('swap should never be called for a downgrade request');
            }
        };
        $this->app->instance(StripeCheckoutService::class, $fake);

        app(SubscriptionPlanChangeService::class)->requestDowngrade($user, $lower);

        $this->assertSame($lower->id, $user->fresh()->pending_plan_id);
        // Still on the higher plan's full allowance - nothing changed yet.
        $this->assertSame(250, app(CreditLedgerService::class)->balance($user, 'plus'));
    }
}
