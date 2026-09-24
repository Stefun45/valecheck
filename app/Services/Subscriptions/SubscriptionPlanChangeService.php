<?php

namespace App\Services\Subscriptions;

use App\Models\CreditTransaction;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\Credits\CreditLedgerService;
use App\Services\Payments\StripeCheckoutService;

/**
 * Upgrades and downgrades deliberately behave differently, per the
 * commercial spec: an upgrade takes effect immediately (Stripe prorates
 * the charge, the account gets the incremental credits straight away),
 * a downgrade is only ever applied at the next renewal - staying on the
 * higher plan/allowance until then is what stops a customer buying a
 * large allocation, using most of it, then immediately downgrading for
 * an unintended credit.
 */
class SubscriptionPlanChangeService
{
    public function __construct(
        private readonly StripeCheckoutService $checkout,
        private readonly CreditLedgerService $ledger,
    ) {}

    public function upgrade(User $user, SubscriptionPlan $newPlan): void
    {
        $currentPlan = $user->activeSubscriptionPlan();
        $currentGrant = $this->currentGrant($user);

        $this->checkout->swapSubscription($user, $newPlan, prorate: true);

        if (! $currentPlan || ! $currentGrant) {
            return;
        }

        $bonus = $newPlan->monthly_credits - $currentPlan->monthly_credits;

        if ($bonus > 0) {
            $this->ledger->grantSubscriptionCredits(
                $user,
                $newPlan,
                $bonus,
                $currentGrant->expires_at,
                "Upgrade bonus: {$bonus} additional credit(s) for the rest of the current period."
            );
        }
    }

    /**
     * No Stripe call and no credit change yet - just records the intent.
     * Applied by StripeWebhookController at the next renewal invoice,
     * alongside that period's fresh credit grant.
     */
    public function requestDowngrade(User $user, SubscriptionPlan $newPlan): void
    {
        $user->update(['pending_plan_id' => $newPlan->id]);
    }

    private function currentGrant(User $user): ?CreditTransaction
    {
        return $user->creditTransactions()
            ->where('type', CreditTransaction::TYPE_SUBSCRIPTION_GRANT)
            ->where('expires_at', '>', now())
            ->latest('expires_at')
            ->first();
    }
}
