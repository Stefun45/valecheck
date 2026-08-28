<?php

namespace App\Services\Pipeline;

use App\Models\Payment;
use App\Models\VehicleCheck;
use Illuminate\Support\Facades\Log;
use Laravel\Cashier\Exceptions\IncompletePayment;
use Throwable;

/**
 * A Check-to-Plus upgrade must never destroy the customer's already-working
 * Check report. Unlike FailedVehicleCheckHandler (which marks a brand-new
 * check as failed, since there's nothing else to show), a failed upgrade
 * reverts the check back to its last-known-good state — completed, type
 * Check — and refunds the upgrade payment specifically, leaving the
 * original purchase and report completely untouched.
 */
class FailedVehicleCheckUpgradeHandler
{
    public function handle(int $vehicleCheckId, string $reason): void
    {
        $check = VehicleCheck::find($vehicleCheckId);

        if (! $check || $check->status === VehicleCheck::STATUS_COMPLETED) {
            return;
        }

        $this->refundUpgradePayment($check);

        $check->update([
            'type' => VehicleCheck::TYPE_CHECK,
            'status' => VehicleCheck::STATUS_COMPLETED,
            'stage' => null,
            'upgraded_at' => null,
            'upgrade_payment_id' => null,
            'failure_reason' => $reason,
        ]);

        Log::warning("Vehicle check #{$vehicleCheckId} upgrade to Plus failed and was reverted/refunded.", ['reason' => $reason]);
    }

    private function refundUpgradePayment(VehicleCheck $check): void
    {
        $payment = Payment::find($check->upgrade_payment_id);

        if (! $payment || $payment->status === Payment::STATUS_REFUNDED) {
            return;
        }

        try {
            if ($payment->stripe_payment_intent_id) {
                $check->user->refund($payment->stripe_payment_intent_id);
            }
        } catch (IncompletePayment|Throwable $e) {
            Log::error("Stripe refund failed for upgrade payment #{$payment->id}: {$e->getMessage()}");
        }

        $payment->update([
            'status' => Payment::STATUS_REFUNDED,
            'refunded_at' => now(),
        ]);
    }
}
