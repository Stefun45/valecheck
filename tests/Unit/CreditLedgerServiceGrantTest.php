<?php

namespace Tests\Unit;

use App\Models\CreditTransaction;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Models\VehicleCheck;
use App\Services\Credits\CreditLedgerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreditLedgerServiceGrantTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_free_grant_increases_the_users_balance(): void
    {
        $user = User::factory()->create();
        $ledger = app(CreditLedgerService::class);

        $ledger->grantFreeCredits($user, VehicleCheck::TYPE_PLUS, 3);

        $this->assertSame(3, $ledger->balance($user, VehicleCheck::TYPE_PLUS));
    }

    public function test_a_free_grant_is_recorded_as_its_own_distinct_type_not_a_purchase(): void
    {
        $user = User::factory()->create();

        $transaction = app(CreditLedgerService::class)->grantFreeCredits($user, VehicleCheck::TYPE_PLUS, 2, 'Goodwill');

        $this->assertSame(CreditTransaction::TYPE_FREE_GRANT, $transaction->type);
        $this->assertNull($transaction->payment_id);
        $this->assertSame('Goodwill', $transaction->note);
    }

    public function test_a_granted_credit_can_actually_be_consumed_like_a_purchased_one(): void
    {
        $user = User::factory()->create();
        $ledger = app(CreditLedgerService::class);
        $ledger->grantFreeCredits($user, VehicleCheck::TYPE_PLUS, 1);

        $this->assertTrue($ledger->hasCredit($user, VehicleCheck::TYPE_PLUS));

        $check = VehicleCheck::factory()->create(['user_id' => $user->id, 'type' => VehicleCheck::TYPE_PLUS]);
        $ledger->consumeCredit($user, VehicleCheck::TYPE_PLUS, $check);

        $this->assertSame(0, $ledger->balance($user, VehicleCheck::TYPE_PLUS));
    }

    private function plan(): SubscriptionPlan
    {
        return SubscriptionPlan::create([
            'name' => 'Pro 25',
            'group' => 'pro',
            'stripe_price_id' => 'price_test',
            'monthly_net' => 99.00,
            'monthly_credits' => 25,
            'additional_credit_net' => 4.99,
            'is_active' => true,
            'sort_order' => 1,
        ]);
    }

    public function test_a_subscription_grant_counts_towards_the_balance_while_still_valid(): void
    {
        $user = User::factory()->create();
        $ledger = app(CreditLedgerService::class);

        $ledger->grantSubscriptionCredits($user, $this->plan(), 25, now()->addDays(10));

        $this->assertSame(25, $ledger->balance($user, VehicleCheck::TYPE_PLUS));
    }

    /**
     * This is what makes "unused monthly credits don't roll over" happen
     * automatically - once expires_at passes, the grant simply stops
     * counting, with no separate void step and a full audit trail intact.
     */
    public function test_an_expired_subscription_grant_no_longer_counts_towards_the_balance(): void
    {
        $user = User::factory()->create();
        $ledger = app(CreditLedgerService::class);

        $ledger->grantSubscriptionCredits($user, $this->plan(), 25, now()->subDay());

        $this->assertSame(0, $ledger->balance($user, VehicleCheck::TYPE_PLUS));
    }

    public function test_a_subscription_grant_is_recorded_as_its_own_distinct_type_with_an_expiry(): void
    {
        $plan = $this->plan();
        $user = User::factory()->create();
        $expiresAt = now()->addDays(20);

        $transaction = app(CreditLedgerService::class)->grantSubscriptionCredits($user, $plan, 25, $expiresAt);

        $this->assertSame(CreditTransaction::TYPE_SUBSCRIPTION_GRANT, $transaction->type);
        $this->assertSame($plan->id, $transaction->subscription_plan_id);
        $this->assertSame($expiresAt->toDateTimeString(), $transaction->expires_at->toDateTimeString());
    }
}
