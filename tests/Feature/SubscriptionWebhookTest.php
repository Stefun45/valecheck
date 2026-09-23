<?php

namespace Tests\Feature;

use App\Models\SubscriptionUsage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionWebhookTest extends TestCase
{
    use RefreshDatabase;

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

    public function test_it_opens_a_subscription_usage_window_from_the_price_id_shape(): void
    {
        config(['valecheck.pricing.subscriptions.dealer.stripe_price' => 'price_dealer_test']);
        $user = User::factory()->create(['stripe_id' => 'cus_test_123']);

        $periodStart = now()->startOfMonth();
        $periodEnd = now()->endOfMonth();

        $this->postJson(route('cashier.webhook'), $this->invoicePaidPayload($user, [
            'price' => ['id' => 'price_dealer_test'],
            'period' => ['start' => $periodStart->timestamp, 'end' => $periodEnd->timestamp],
        ]))->assertOk();

        $usage = SubscriptionUsage::where('user_id', $user->id)->firstOrFail();
        $this->assertSame('dealer', $usage->plan);
        $this->assertSame('plus', $usage->report_type);
        $this->assertSame(30, $usage->allowance);
        $this->assertSame(0, $usage->used);
        $this->assertSame($periodStart->toDateString(), $usage->period_start->toDateString());
        $this->assertSame($periodEnd->toDateString(), $usage->period_end->toDateString());
    }

    public function test_it_opens_a_subscription_usage_window_from_the_newer_pricing_shape(): void
    {
        // Stripe's newer invoice line-item schema nests the price under
        // pricing.price_details.price rather than a top-level price.id -
        // the code checks both, this test exercises the second path.
        config(['valecheck.pricing.subscriptions.trader.stripe_price' => 'price_trader_test']);
        $user = User::factory()->create(['stripe_id' => 'cus_test_456']);

        $periodStart = now()->startOfMonth();
        $periodEnd = now()->endOfMonth();

        $this->postJson(route('cashier.webhook'), $this->invoicePaidPayload($user, [
            'pricing' => ['price_details' => ['price' => 'price_trader_test']],
            'period' => ['start' => $periodStart->timestamp, 'end' => $periodEnd->timestamp],
        ]))->assertOk();

        $usage = SubscriptionUsage::where('user_id', $user->id)->firstOrFail();
        $this->assertSame('trader', $usage->plan);
        $this->assertSame(5, $usage->allowance);
    }

    public function test_it_skips_silently_when_the_price_does_not_match_any_configured_plan(): void
    {
        $user = User::factory()->create(['stripe_id' => 'cus_test_789']);

        $this->postJson(route('cashier.webhook'), $this->invoicePaidPayload($user, [
            'price' => ['id' => 'price_unrecognised'],
            'period' => ['start' => now()->timestamp, 'end' => now()->addMonth()->timestamp],
        ]))->assertOk();

        $this->assertSame(0, SubscriptionUsage::where('user_id', $user->id)->count());
    }

    public function test_it_is_safe_to_run_more_than_once_for_the_same_invoice(): void
    {
        config(['valecheck.pricing.subscriptions.pro.stripe_price' => 'price_pro_test']);
        $user = User::factory()->create(['stripe_id' => 'cus_test_999']);

        $payload = $this->invoicePaidPayload($user, [
            'price' => ['id' => 'price_pro_test'],
            'period' => ['start' => now()->startOfMonth()->timestamp, 'end' => now()->endOfMonth()->timestamp],
        ]);

        $this->postJson(route('cashier.webhook'), $payload)->assertOk();
        $this->postJson(route('cashier.webhook'), $payload)->assertOk();

        $this->assertSame(1, SubscriptionUsage::where('user_id', $user->id)->count());
    }
}
