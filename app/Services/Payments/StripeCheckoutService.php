<?php

namespace App\Services\Payments;

use App\Models\Payment;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Models\UserProductPrice;
use App\Models\VehicleCheck;
use App\Services\Discounts\DiscountCodeService;
use App\Services\Pricing\PricingService;
use InvalidArgumentException;
use Laravel\Cashier\Checkout;

class StripeCheckoutService
{
    public function __construct(
        private readonly PricingService $pricing,
        private readonly DiscountCodeService $discounts,
    ) {}

    public function checkoutForVehicleCheck(VehicleCheck $check): Checkout
    {
        // The true, undiscounted standard price - used only to record what
        // this would have cost without any user override, site-wide
        // promotion or discount code, whichever of those (if any) ends up
        // applying. Not the same as $priceBeforeCode below, which already
        // reflects a user override or a live promotion.
        $standardPrice = $this->pricing->standardPrice($check->type);
        $priceBeforeCode = $this->pricing->forProduct($check->type, $check->user);
        $label = config("valecheck.pricing.{$check->type}.label");

        // Re-validated here, at the point money actually changes hands —
        // never trust that a code the customer typed earlier in the wizard
        // is still valid (it may have expired or been exhausted since).
        $discount = $check->discount_code
            ? $this->discounts->find($check->discount_code, $check->type, $check->user)
            : null;

        $price = $discount
            ? $this->pricing->breakdown($this->discounts->apply($discount, $priceBeforeCode->gross))
            : $priceBeforeCode;

        $payment = Payment::create([
            'user_id' => $check->user_id,
            'discount_code_id' => $discount?->id,
            'type' => $check->type,
            'description' => "{$label} — {$check->registration}",
            'gross' => $price->gross,
            'original_gross' => $this->hasUserOverride($check->user, $check->type)
                ? null
                : $this->originalGrossIfDiscounted($price->gross, $standardPrice->gross),
            'net' => $price->net,
            'vat' => $price->vat,
            'vat_rate' => $price->vatRate,
            'currency' => $price->currency,
            'status' => Payment::STATUS_PENDING,
        ]);

        return $check->user->checkoutCharge(
            $this->toMinorUnits($price->gross),
            $label,
            1,
            [
                'success_url' => route('vehicle-checks.show', $check)."?paid=1&payment={$payment->id}",
                'cancel_url' => route('vehicle-checks.start'),
                'metadata' => array_filter([
                    'kind' => 'vehicle_check',
                    'vehicle_check_id' => (string) $check->id,
                    'payment_id' => (string) $payment->id,
                    'discount_code_id' => $discount ? (string) $discount->id : null,
                ]),
            ],
        );
    }

    public function checkoutForVehicleCheckUpgrade(VehicleCheck $check): Checkout
    {
        $standardPrice = $this->pricing->standardPrice('plus_upgrade');
        $price = $this->pricing->forProduct('plus_upgrade', $check->user);
        $label = config('valecheck.pricing.plus_upgrade.label');

        $payment = Payment::create([
            'user_id' => $check->user_id,
            'type' => Payment::TYPE_PLUS_UPGRADE,
            'description' => "{$label} — {$check->registration}",
            'gross' => $price->gross,
            'original_gross' => $this->hasUserOverride($check->user, 'plus_upgrade')
                ? null
                : $this->originalGrossIfDiscounted($price->gross, $standardPrice->gross),
            'net' => $price->net,
            'vat' => $price->vat,
            'vat_rate' => $price->vatRate,
            'currency' => $price->currency,
            'status' => Payment::STATUS_PENDING,
        ]);

        return $check->user->checkoutCharge(
            $this->toMinorUnits($price->gross),
            $label,
            1,
            [
                'success_url' => route('vehicle-checks.show', $check)."?paid=1&payment={$payment->id}",
                'cancel_url' => route('vehicle-checks.show', $check),
                'metadata' => [
                    'kind' => 'vehicle_check_upgrade',
                    'vehicle_check_id' => (string) $check->id,
                    'payment_id' => (string) $payment->id,
                ],
            ],
        );
    }

    public function checkoutForCreditPack(User $user, string $packKey): Checkout
    {
        $price = $this->pricing->forCreditPack($packKey);
        $credits = $this->pricing->creditPackCredits($packKey);
        $pack = config("valecheck.pricing.credit_packs.{$packKey}") ?? throw new InvalidArgumentException("Unknown credit pack [{$packKey}].");

        $payment = Payment::create([
            'user_id' => $user->id,
            'type' => Payment::TYPE_CREDIT_PACK,
            'description' => $pack['label'],
            'gross' => $price->gross,
            'net' => $price->net,
            'vat' => $price->vat,
            'vat_rate' => $price->vatRate,
            'currency' => $price->currency,
            'status' => Payment::STATUS_PENDING,
        ]);

        return $user->checkoutCharge(
            $this->toMinorUnits($price->gross),
            $pack['label'],
            1,
            [
                'success_url' => route('dashboard')."?paid=1&payment={$payment->id}",
                'cancel_url' => route('dashboard'),
                'metadata' => [
                    'kind' => 'credit_pack',
                    'payment_id' => (string) $payment->id,
                    'report_type' => $pack['report_type'],
                    'credits' => (string) $credits,
                ],
            ],
        );
    }

    public function checkoutForSubscription(User $user, SubscriptionPlan $plan): Checkout
    {
        return $user->newSubscription('default', $this->resolveStripePriceId($plan))->checkout([
            'success_url' => route('dashboard').'?subscribed=1',
            'cancel_url' => route('dashboard'),
            'metadata' => [
                'kind' => 'subscription',
                'plan_id' => (string) $plan->id,
            ],
        ]);
    }

    /**
     * Changes an already-active subscription to a different plan via
     * Stripe's own proration (an upgrade) or with proration suppressed
     * (a downgrade being applied exactly at renewal - see
     * StripeWebhookController) - no Checkout redirect needed, the payment
     * method is already on file. Only ever called once the caller has
     * confirmed the user is genuinely subscribed; calling this on a user
     * with no subscription throws (Cashier has nothing to swap).
     */
    public function swapSubscription(User $user, SubscriptionPlan $plan, bool $prorate = true): void
    {
        // Resolved before touching the subscription object - PHP attempts
        // the ->swap() call before evaluating its argument, so a bad plan
        // would otherwise surface as a confusing "call to swap() on null"
        // rather than this method's own clear error.
        $priceId = $this->resolveStripePriceId($plan);
        $subscription = $user->subscription('default');

        if ($prorate) {
            $subscription->swap($priceId);
        } else {
            $subscription->noProrate()->swap($priceId);
        }
    }

    /**
     * A subscriber topping up when their monthly allowance runs out - the
     * same economic event as a one-off credit pack purchase, just priced
     * from their own plan's additional_credit_net rather than a
     * config-defined pack tier, so it reuses the exact same
     * StripeCheckoutCompletionHandler "credit_pack" handling with no new
     * code path needed there.
     */
    public function checkoutForAdditionalCredits(User $user, SubscriptionPlan $plan, int $quantity): Checkout
    {
        $unitPrice = $this->pricing->forAdditionalCredit($plan);
        $totalGross = round($unitPrice->gross * $quantity, 2);
        $label = "{$quantity} additional credit(s) ({$plan->name})";

        $payment = Payment::create([
            'user_id' => $user->id,
            'type' => Payment::TYPE_CREDIT_PACK,
            'description' => $label,
            'gross' => $totalGross,
            'net' => round($unitPrice->net * $quantity, 2),
            'vat' => round($unitPrice->vat * $quantity, 2),
            'vat_rate' => $unitPrice->vatRate,
            'currency' => $unitPrice->currency,
            'status' => Payment::STATUS_PENDING,
        ]);

        return $user->checkoutCharge(
            $this->toMinorUnits($totalGross),
            $label,
            1,
            [
                'success_url' => route('dashboard')."?paid=1&payment={$payment->id}",
                'cancel_url' => route('dashboard'),
                'metadata' => [
                    'kind' => 'credit_pack',
                    'payment_id' => (string) $payment->id,
                    'report_type' => VehicleCheck::TYPE_PLUS,
                    'credits' => (string) $quantity,
                ],
            ],
        );
    }

    private function resolveStripePriceId(SubscriptionPlan $plan): string
    {
        if (empty($plan->stripe_price_id)) {
            throw new InvalidArgumentException("No Stripe price is configured for the [{$plan->name}] subscription plan.");
        }

        return $plan->stripe_price_id;
    }

    private function toMinorUnits(float $gross): int
    {
        return (int) round($gross * 100);
    }

    /**
     * Null when nothing actually reduced the price - keeps original_gross
     * meaning exactly what it says regardless of which mechanism (a live
     * site promotion, a discount code, or both) is responsible for the
     * reduction.
     */
    private function originalGrossIfDiscounted(float $chargedGross, float $standardGross): ?float
    {
        return abs($chargedGross - $standardGross) > 0.001 ? $standardGross : null;
    }

    /**
     * A bespoke UserProductPrice is a standing, deliberate price for that
     * one account - not something this specific payment "discounted", so
     * original_gross must stay null for it regardless of how it compares
     * to the general standard price (this mirrors DiscountCodeService,
     * which already refuses a code outright for these accounts, so a
     * bespoke price is the only way this account's charged price can
     * ever differ from standard in the first place).
     */
    private function hasUserOverride(?User $user, string $type): bool
    {
        return $user
            ? UserProductPrice::where('user_id', $user->id)->where('type', $type)->exists()
            : false;
    }
}
