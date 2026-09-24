<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\Credits\CreditLedgerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdditionalCreditsPurchaseTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['valecheck.subscriptions_enabled' => true, 'cashier.secret' => 'sk_test_fake']);
    }

    private function plan(): SubscriptionPlan
    {
        return SubscriptionPlan::create([
            'name' => 'Pro 25',
            'group' => 'pro',
            'stripe_price_id' => 'price_pro25',
            'monthly_net' => 99.00,
            'monthly_credits' => 25,
            'additional_credit_net' => 4.99,
            'is_active' => true,
            'sort_order' => 1,
        ]);
    }

    public function test_a_subscriber_buying_additional_credits_creates_a_correctly_priced_pending_payment(): void
    {
        $plan = $this->plan();
        $user = User::factory()->create();
        app(CreditLedgerService::class)->grantSubscriptionCredits($user, $plan, 25, now()->addDays(10));
        $this->actingAs($user);

        try {
            $this->post(route('billing.additional-credits'), ['quantity' => 5]);
        } catch (\Throwable) {
            // Expected - no real Stripe key in this environment. The
            // Payment row this test cares about is created just before
            // that, so the price that was actually going to be charged is
            // still verifiable (same pattern as StripeCheckoutServiceCustomPriceTest).
        }

        $payment = Payment::where('user_id', $user->id)->firstOrFail();
        // 5 credits x £4.99 net = £24.95 net -> £29.94 gross
        $this->assertEqualsWithDelta(29.94, (float) $payment->gross, 0.01);
    }

    public function test_a_user_with_no_active_subscription_cannot_buy_additional_credits(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->post(route('billing.additional-credits'), ['quantity' => 5])->assertForbidden();
    }
}
