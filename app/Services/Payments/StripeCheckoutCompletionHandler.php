<?php

namespace App\Services\Payments;

use App\Models\Commission;
use App\Models\DiscountCode;
use App\Models\DiscountCodeRedemption;
use App\Models\Payment;
use App\Models\Referral;
use App\Models\VehicleCheck;
use App\Services\Affiliate\AffiliateCommissionService;
use App\Services\Credits\CreditLedgerService;
use App\Services\Pipeline\VehicleCheckPipeline;
use App\Services\Reports\ReportPdfService;
use Illuminate\Support\Facades\Log;

/**
 * Turns a decoded Stripe checkout.session.completed payload into the local
 * side effects (mark payment paid, dispatch the pipeline, grant credits,
 * create an affiliate commission, record a discount-code redemption).
 * Deliberately takes a plain array rather than a Stripe SDK object so it
 * can be unit tested without any real Stripe API calls.
 */
class StripeCheckoutCompletionHandler
{
    public function __construct(
        private readonly CreditLedgerService $ledger,
        private readonly VehicleCheckPipeline $pipeline,
        private readonly AffiliateCommissionService $commissions,
        private readonly ReportPdfService $pdfService,
    ) {}

    /**
     * @param  array<string, mixed>  $session
     */
    public function handle(array $session): void
    {
        if (($session['payment_status'] ?? null) !== 'paid') {
            return;
        }

        $metadata = $session['metadata'] ?? [];
        $paymentId = $metadata['payment_id'] ?? null;

        if (! $paymentId) {
            Log::warning('Stripe checkout.session.completed received without a payment_id in metadata.', $session);

            return;
        }

        $payment = Payment::find($paymentId);

        if (! $payment || $payment->status === Payment::STATUS_PAID) {
            return;
        }

        $payment->update([
            'status' => Payment::STATUS_PAID,
            'stripe_checkout_session_id' => $session['id'] ?? null,
            'stripe_payment_intent_id' => $session['payment_intent'] ?? null,
        ]);

        $this->createCommissionIfReferred($payment);
        $this->recordDiscountRedemption($metadata, $payment);

        match ($metadata['kind'] ?? null) {
            'vehicle_check' => $this->handleVehicleCheckPayment($metadata, $payment),
            'vehicle_check_upgrade' => $this->handleVehicleCheckUpgradePayment($metadata, $payment),
            'credit_pack' => $this->handleCreditPackPayment($metadata, $payment),
            default => Log::warning("Stripe checkout.session.completed with unknown metadata kind for payment #{$payment->id}."),
        };
    }

    private function handleVehicleCheckPayment(array $metadata, Payment $payment): void
    {
        $check = VehicleCheck::find($metadata['vehicle_check_id'] ?? null);

        if (! $check || $check->status !== VehicleCheck::STATUS_PENDING) {
            return;
        }

        $check->update(['payment_id' => $payment->id]);

        $this->pipeline->dispatch($check);
    }

    /**
     * Reuses the same VehicleCheck row rather than creating a new one —
     * only eligible (completed Check) rows are flipped, so a re-delivered
     * webhook or a stale/already-upgraded check is a safe no-op.
     */
    private function handleVehicleCheckUpgradePayment(array $metadata, Payment $payment): void
    {
        $check = VehicleCheck::find($metadata['vehicle_check_id'] ?? null);

        if (! $check || ! $check->isUpgradeable()) {
            return;
        }

        // The existing Check-only PDF must not be served after the report
        // becomes Plus — without this, GenerateReport's later call to
        // ReportPdfService::generate() would see a PDF already exists and
        // skip regenerating it entirely, silently leaving the old content.
        $this->pdfService->invalidate($check);

        $check->update([
            'type' => VehicleCheck::TYPE_PLUS,
            'status' => VehicleCheck::STATUS_PROCESSING,
            'upgrade_payment_id' => $payment->id,
            'upgraded_at' => now(),
        ]);

        $this->pipeline->dispatchUpgrade($check);
    }

    private function handleCreditPackPayment(array $metadata, Payment $payment): void
    {
        $this->ledger->grantPurchasedCredits(
            $payment->user,
            $metadata['report_type'] ?? VehicleCheck::TYPE_REBUILD,
            (int) ($metadata['credits'] ?? 0),
            $payment,
        );
    }

    /**
     * Only ever runs once per payment — this method is reached exclusively
     * via the confirmed-payment path, which is itself guarded upstream by
     * the "already paid" idempotency check, so a re-delivered webhook can
     * never create a duplicate commission.
     */
    private function createCommissionIfReferred(Payment $payment): void
    {
        $referral = Referral::where('referred_user_id', $payment->user_id)->first();

        if (! $referral) {
            return;
        }

        $amount = $this->commissions->commissionFor($payment->type);

        if ($amount <= 0) {
            return;
        }

        Commission::create([
            'creator_id' => $referral->creator_id,
            'referral_id' => $referral->id,
            'payment_id' => $payment->id,
            'type' => 'one_off',
            'amount' => $amount,
            'status' => Commission::STATUS_PENDING,
        ]);
    }

    /**
     * Redemption is only ever recorded here, on confirmed payment — an
     * abandoned Stripe checkout never consumes a redemption slot.
     */
    private function recordDiscountRedemption(array $metadata, Payment $payment): void
    {
        $discountCodeId = $metadata['discount_code_id'] ?? null;

        if (! $discountCodeId) {
            return;
        }

        $discount = DiscountCode::find($discountCodeId);

        if (! $discount) {
            return;
        }

        DiscountCodeRedemption::create([
            'discount_code_id' => $discount->id,
            'user_id' => $payment->user_id,
            'payment_id' => $payment->id,
            'amount_discounted' => (float) ($payment->original_gross ?? 0) - (float) $payment->gross,
        ]);

        $discount->increment('times_redeemed');
    }
}
