<?php

namespace App\Services\Credits;

use App\Models\CreditTransaction;
use App\Models\Payment;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Models\VehicleCheck;
use Carbon\Carbon;
use RuntimeException;

/**
 * Credit balances are never stored as a single column on the user — every
 * grant, purchase, consumption and refund is a row in credit_transactions,
 * and a balance is always derived by summing that ledger.
 */
class CreditLedgerService
{
    /**
     * Expired subscription-grant rows are excluded, not deleted or
     * zeroed - this is what makes "unused monthly credits don't roll
     * over" happen automatically the moment a period's expires_at
     * passes, with no separate void step and a full audit trail intact.
     */
    public function balance(User $user, string $reportType): int
    {
        return (int) $user->creditTransactions()
            ->where('report_type', $reportType)
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
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

    /**
     * A manual, admin-granted credit — free lookups given at the site
     * owner's discretion (goodwill, testing, a VIP account), never tied to
     * a real payment. Deliberately a distinct type from
     * grantPurchasedCredits so these never get counted as revenue or
     * mistaken for a genuine Stripe purchase in any report that groups by
     * transaction type.
     */
    public function grantFreeCredits(User $user, string $reportType, int $amount, ?string $note = null): CreditTransaction
    {
        return CreditTransaction::create([
            'user_id' => $user->id,
            'type' => CreditTransaction::TYPE_FREE_GRANT,
            'report_type' => $reportType,
            'amount' => $amount,
            'note' => $note ?? "Manually granted {$amount} {$reportType} credit(s).",
        ]);
    }

    /**
     * A subscription plan's monthly credit allocation - amount can be a
     * partial top-up (e.g. the incremental credits an upgrade grants for
     * the rest of the current period), not always the plan's full
     * monthly_credits. expiresAt is always the end of the billing period
     * this grant belongs to, whichever period that turns out to be.
     */
    public function grantSubscriptionCredits(User $user, SubscriptionPlan $plan, int $amount, Carbon $expiresAt, ?string $note = null): CreditTransaction
    {
        return CreditTransaction::create([
            'user_id' => $user->id,
            'type' => CreditTransaction::TYPE_SUBSCRIPTION_GRANT,
            'report_type' => 'plus',
            'amount' => $amount,
            'expires_at' => $expiresAt,
            'subscription_plan_id' => $plan->id,
            'note' => $note ?? "Granted {$amount} credit(s) from the {$plan->name} plan.",
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
