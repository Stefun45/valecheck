<?php

namespace App\Services\Pipeline;

use App\Models\Payment;
use App\Models\VehicleCheck;
use App\Services\Credits\CreditLedgerService;
use Illuminate\Support\Facades\Log;
use Laravel\Cashier\Exceptions\IncompletePayment;
use Throwable;

/**
 * Vehicle checks can fail (transient provider errors, missing AI
 * configuration, etc.). Whatever funded the report is given back so the
 * customer never loses money or a credit for a report that never completed.
 */
class FailedVehicleCheckHandler
{
    public function __construct(private readonly CreditLedgerService $ledger) {}

    public function handle(int $vehicleCheckId, string $reason): void
    {
        $check = VehicleCheck::find($vehicleCheckId);

        if (! $check || $check->status === VehicleCheck::STATUS_FAILED) {
            return;
        }

        $check->update([
            'status' => VehicleCheck::STATUS_FAILED,
            'stage' => null,
            'failure_reason' => $reason,
            'completed_at' => now(),
        ]);

        if ($check->funding_source === 'credit' && $check->type === VehicleCheck::TYPE_PLUS) {
            // Nothing to refund - a Plus credit is only ever deducted once
            // GenerateReport confirms the report actually generated (see
            // VehicleCheckOrderService::submit()), so a failure before
            // that point never deducted anything in the first place.
        } else {
            match ($check->funding_source) {
                'free', 'credit' => $this->ledger->refundCredit($check->user, $check->type, $check),
                'purchase' => $this->refundPayment($check),
                default => null,
            };
        }

        Log::warning("Vehicle check #{$vehicleCheckId} failed and was refunded.", ['reason' => $reason]);
    }

    private function refundPayment(VehicleCheck $check): void
    {
        $payment = Payment::find($check->payment_id);

        if (! $payment || $payment->status === Payment::STATUS_REFUNDED) {
            return;
        }

        try {
            if ($payment->stripe_payment_intent_id) {
                $check->user->refund($payment->stripe_payment_intent_id);
            }
        } catch (IncompletePayment|Throwable $e) {
            Log::error("Stripe refund failed for payment #{$payment->id}: {$e->getMessage()}");
        }

        $payment->update([
            'status' => Payment::STATUS_REFUNDED,
            'refunded_at' => now(),
        ]);
    }
}
