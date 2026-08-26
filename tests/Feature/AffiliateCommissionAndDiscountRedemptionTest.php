<?php

namespace Tests\Feature;

use App\Models\Commission;
use App\Models\Creator;
use App\Models\DiscountCode;
use App\Models\DiscountCodeRedemption;
use App\Models\Payment;
use App\Models\Referral;
use App\Models\User;
use App\Services\Payments\StripeCheckoutCompletionHandler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AffiliateCommissionAndDiscountRedemptionTest extends TestCase
{
    use RefreshDatabase;

    private function paidPayment(User $user, string $type = 'check', float $gross = 8.99): Payment
    {
        return Payment::create([
            'user_id' => $user->id,
            'type' => $type,
            'description' => 'Test',
            'gross' => $gross,
            'net' => $gross,
            'vat' => 0,
            'vat_rate' => 0.20,
            'currency' => 'GBP',
            'status' => Payment::STATUS_PENDING,
        ]);
    }

    private function completeWebhook(Payment $payment, array $extraMetadata = []): void
    {
        app(StripeCheckoutCompletionHandler::class)->handle([
            'id' => 'cs_test_'.$payment->id,
            'payment_intent' => 'pi_test_'.$payment->id,
            'payment_status' => 'paid',
            'metadata' => array_merge([
                'kind' => 'vehicle_check',
                'payment_id' => (string) $payment->id,
            ], $extraMetadata),
        ]);
    }

    public function test_a_commission_is_created_when_the_payer_was_referred(): void
    {
        $affiliateUser = User::factory()->create();
        $creator = Creator::create(['user_id' => $affiliateUser->id, 'name' => 'Jane', 'referral_code' => 'JANE10']);

        $buyer = User::factory()->create();
        Referral::create(['creator_id' => $creator->id, 'referred_user_id' => $buyer->id, 'attributed_at' => now()]);

        $payment = $this->paidPayment($buyer, 'check');
        $this->completeWebhook($payment);

        $this->assertDatabaseHas('commissions', [
            'creator_id' => $creator->id,
            'payment_id' => $payment->id,
            'status' => Commission::STATUS_PENDING,
        ]);
    }

    public function test_no_commission_is_created_when_the_payer_was_not_referred(): void
    {
        $buyer = User::factory()->create();
        $payment = $this->paidPayment($buyer, 'check');

        $this->completeWebhook($payment);

        $this->assertDatabaseCount('commissions', 0);
    }

    public function test_a_re_delivered_webhook_never_creates_a_duplicate_commission(): void
    {
        $affiliateUser = User::factory()->create();
        $creator = Creator::create(['user_id' => $affiliateUser->id, 'name' => 'Jane', 'referral_code' => 'JANE10']);

        $buyer = User::factory()->create();
        Referral::create(['creator_id' => $creator->id, 'referred_user_id' => $buyer->id, 'attributed_at' => now()]);

        $payment = $this->paidPayment($buyer, 'check');

        $this->completeWebhook($payment);
        $this->completeWebhook($payment);

        $this->assertDatabaseCount('commissions', 1);
    }

    public function test_a_discount_redemption_is_recorded_on_confirmed_payment(): void
    {
        $discount = DiscountCode::create(['code' => 'SAVE10', 'type' => 'percentage', 'value' => 10]);
        $buyer = User::factory()->create();

        $payment = $this->paidPayment($buyer, 'check', 8.09);
        $payment->update(['discount_code_id' => $discount->id, 'original_gross' => 8.99]);

        $this->completeWebhook($payment, ['discount_code_id' => (string) $discount->id]);

        $this->assertDatabaseHas('discount_code_redemptions', [
            'discount_code_id' => $discount->id,
            'user_id' => $buyer->id,
            'payment_id' => $payment->id,
        ]);
        $this->assertSame(1, $discount->fresh()->times_redeemed);
    }

    public function test_a_re_delivered_webhook_never_double_counts_a_redemption(): void
    {
        $discount = DiscountCode::create(['code' => 'SAVE10', 'type' => 'percentage', 'value' => 10]);
        $buyer = User::factory()->create();

        $payment = $this->paidPayment($buyer, 'check', 8.09);
        $payment->update(['discount_code_id' => $discount->id, 'original_gross' => 8.99]);

        $this->completeWebhook($payment, ['discount_code_id' => (string) $discount->id]);
        $this->completeWebhook($payment, ['discount_code_id' => (string) $discount->id]);

        $this->assertSame(1, $discount->fresh()->times_redeemed);
        $this->assertSame(1, DiscountCodeRedemption::count());
    }
}
