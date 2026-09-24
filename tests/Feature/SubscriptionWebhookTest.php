<?php

namespace Tests\Feature;

use App\Models\CreditTransaction;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\Credits\CreditLedgerService;
use App\Services\Payments\StripeCheckoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionWebhookTest extends TestCase
{
    use RefreshDatabase;

    private function plan(string $name, string $priceId, int $credits, int $sortOrder = 1): SubscriptionPlan
    {
        return SubscriptionPlan::create([
            'name' => $name,
            'group' => 'pro',
            'stripe_price_id' => $priceId,
            'monthly_net' => 99.00,
            'monthly_credits' => $credits,
            'additional_credit_net' => 4.99,
            'is_active' => true,
            'sort_order' => $sortOrder,
        ]);
    }

    /**
     * @param  array<string, mixed>  $line
     */
    private function invoicePaidPayload(User $user, array $line): array
    {
        return [
            'type' => 'invoice.payment_succeeded',
            'data' => [
                'object' => [
                    'customer' => $user->stripe_id,
                    'subscription' => 'sub_test_123',
                    'lines' => ['data' => [$line]],
                ],
            ],
        ];
    }

    public function test_it_grants_subscription_credits_from_the_price_id_shape(): void
    {
        $plan = $this->plan('Dealer 100', 'price_dealer_test', 100);
        $user = User::factory()->create(['stripe_id' => 'cus_test_123']);

        $periodStart = now()->startOfMonth();
        $periodEnd = now()->endOfMonth();

        $this->postJson(route('cashier.webhook'), $this->invoicePaidPayload($user, [
            'price' => ['id' => 'price_dealer_test'],
            'period' => ['start' => $periodStart->timestamp, 'end' => $periodEnd->timestamp],
        ]))->assertOk();

        $grant = CreditTransaction::where('user_id', $user->id)->where('type', CreditTransaction::TYPE_SUBSCRIPTION_GRANT)->firstOrFail();
        $this->assertSame($plan->id, $grant->subscription_plan_id);
        $this->assertSame('plus', $grant->report_type);
        $this->assertSame(100, $grant->amount);
        $this->assertSame($periodEnd->toDateString(), $grant->expires_at->toDateString());
        $this->assertSame(100, app(CreditLedgerService::class)->balance($user, 'plus'));
    }

    public function test_it_grants_subscription_credits_from_the_newer_pricing_shape(): void
    {
        // Stripe's newer invoice line-item schema nests the price under
        // pricing.price_details.price rather than a top-level price.id -
        // the code checks both, this test exercises the second path.
        $plan = $this->plan('Pro 25', 'price_pro_test', 25);
        $user = User::factory()->create(['stripe_id' => 'cus_test_456']);

        $periodStart = now()->startOfMonth();
        $periodEnd = now()->endOfMonth();

        $this->postJson(route('cashier.webhook'), $this->invoicePaidPayload($user, [
            'pricing' => ['price_details' => ['price' => 'price_pro_test']],
            'period' => ['start' => $periodStart->timestamp, 'end' => $periodEnd->timestamp],
        ]))->assertOk();

        $grant = CreditTransaction::where('user_id', $user->id)->where('type', CreditTransaction::TYPE_SUBSCRIPTION_GRANT)->firstOrFail();
        $this->assertSame($plan->id, $grant->subscription_plan_id);
        $this->assertSame(25, $grant->amount);
    }

    public function test_it_skips_silently_when_the_price_does_not_match_any_configured_plan(): void
    {
        $user = User::factory()->create(['stripe_id' => 'cus_test_789']);

        $this->postJson(route('cashier.webhook'), $this->invoicePaidPayload($user, [
            'price' => ['id' => 'price_unrecognised'],
            'period' => ['start' => now()->timestamp, 'end' => now()->addMonth()->timestamp],
        ]))->assertOk();

        $this->assertSame(0, CreditTransaction::where('user_id', $user->id)->count());
    }

    public function test_it_is_safe_to_run_more_than_once_for_the_same_invoice(): void
    {
        $this->plan('Pro 25', 'price_pro_test', 25);
        $user = User::factory()->create(['stripe_id' => 'cus_test_999']);

        $payload = $this->invoicePaidPayload($user, [
            'price' => ['id' => 'price_pro_test'],
            'period' => ['start' => now()->startOfMonth()->timestamp, 'end' => now()->endOfMonth()->timestamp],
        ]);

        $this->postJson(route('cashier.webhook'), $payload)->assertOk();
        $this->postJson(route('cashier.webhook'), $payload)->assertOk();

        $this->assertSame(1, CreditTransaction::where('user_id', $user->id)->where('type', CreditTransaction::TYPE_SUBSCRIPTION_GRANT)->count());
        $this->assertSame(25, app(CreditLedgerService::class)->balance($user, 'plus'));
    }

    public function test_invoice_upcoming_applies_a_pending_downgrade_before_renewal(): void
    {
        $lower = $this->plan('Dealer 100', 'price_dealer_100', 100, 1);
        $user = User::factory()->create(['stripe_id' => 'cus_test_down', 'pending_plan_id' => $lower->id]);

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

        $payload = [
            'type' => 'invoice.upcoming',
            'data' => [
                'object' => [
                    'customer' => $user->stripe_id,
                    'subscription' => 'sub_down_test',
                ],
            ],
        ];

        $this->postJson(route('cashier.webhook'), $payload)->assertOk();

        $this->assertSame([['plan_id' => $lower->id, 'prorate' => false]], $fake->calls);
        $this->assertNull($user->fresh()->pending_plan_id);
    }

    public function test_a_user_with_no_pending_downgrade_is_unaffected_by_invoice_upcoming(): void
    {
        $user = User::factory()->create(['stripe_id' => 'cus_test_noop']);

        $payload = [
            'type' => 'invoice.upcoming',
            'data' => [
                'object' => [
                    'customer' => $user->stripe_id,
                    'subscription' => 'sub_noop',
                ],
            ],
        ];

        $this->postJson(route('cashier.webhook'), $payload)->assertOk();

        $this->assertNull($user->fresh()->pending_plan_id);
    }
}
