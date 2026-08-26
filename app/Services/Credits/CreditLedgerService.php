<?php

namespace App\Services\Credits;

use App\Models\CreditTransaction;
use App\Models\Payment;
use App\Models\User;
use App\Models\VehicleCheck;
use RuntimeException;

/**
 * Credit balances are never stored as a single column on the user — every
 * grant, purchase, consumption and refund is a row in credit_transactions,
 * and a balance is always derived by summing that ledger.
 */
class CreditLedgerService
{
    public function balance(User $user, string $reportType): int
    {
        return (int) $user->creditTransactions()
            ->where('report_type', $reportType)
            ->sum('amount');
    }

    public function hasCredit(User $user, string $reportType): bool
    {
        return $this->balance($user, $reportType) > 0;
    }

    public function grantPurchasedCredits(User $user, string $reportType, int $amount, ?Payment $payment = null, ?string $note = null): CreditTransaction
    {
        return CreditTransaction::create([
            'user_id' => $user->id,
            'type' => CreditTransaction::TYPE_PURCHASE,
            'report_type' => $reportType,
            'amount' => $amount,
            'payment_id' => $payment?->id,
            'note' => $note ?? "Purchased {$amount} {$reportType} credit(s).",
        ]);
    }

    public function consumeCredit(User $user, string $reportType, VehicleCheck $vehicleCheck): CreditTransaction
    {
        if (! $this->hasCredit($user, $reportType)) {
            throw new RuntimeException("User #{$user->id} has no {$reportType} credits to consume.");
        }

        return CreditTransaction::create([
            'user_id' => $user->id,
            'type' => CreditTransaction::TYPE_CONSUMPTION,
            'report_type' => $reportType,
            'amount' => -1,
            'vehicle_check_id' => $vehicleCheck->id,
            'note' => "Consumed for vehicle check #{$vehicleCheck->id}.",
        ]);
    }

    public function refundCredit(User $user, string $reportType, VehicleCheck $vehicleCheck): CreditTransaction
    {
        return CreditTransaction::create([
            'user_id' => $user->id,
            'type' => CreditTransaction::TYPE_REFUND,
            'report_type' => $reportType,
            'amount' => 1,
            'vehicle_check_id' => $vehicleCheck->id,
            'note' => "Refunded after failed vehicle check #{$vehicleCheck->id}.",
        ]);
    }
}
