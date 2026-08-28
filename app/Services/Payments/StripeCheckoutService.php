<?php

namespace App\Services\Payments;

use App\Models\Payment;
use App\Models\User;
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
        $originalPrice = $this->pricing->forProduct($check->type);
        $label = config("valecheck.pricing.{$check->type}.label");

        // Re-validated here, at the point money actually changes hands —
        // never trust that a code the customer typed earlier in the wizard
        // is still valid (it may have expired or been exhausted since).
        $discount = $check->discount_code
            ? $this->discounts->find($check->discount_code, $check->type)
            : null;

        $price = $discount
            ? $this->pricing->breakdown($this->discounts->apply($discount, $originalPrice->gross))
            : $originalPrice;

        $payment = Payment::create([
            'user_id' => $check->user_id,
            'discount_code_id' => $discount?->id,
            'type' => $check->type,
            'description' => "{$label} — {$check->registration}",
            'gross' => $price->gross,
            'original_gross' => $discount ? $originalPrice->gross : null,
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
                'success_url' => route('vehicle-checks.show', $check).'?paid=1',
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
        $price = $this->pricing->forProduct('plus_upgrade');
        $label = config('valecheck.pricing.plus_upgrade.label');

        $payment = Payment::create([
            'user_id' => $check->user_id,
            'type' => Payment::TYPE_PLUS_UPGRADE,
            'description' => "{$label} — {$check->registration}",
            'gross' => $price->gross,
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
                'success_url' => route('vehicle-checks.show', $check).'?paid=1',
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
                'success_url' => route('dashboard').'?paid=1',
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

    public function checkoutForSubscription(User $user, string $plan): Checkout
    {
        $priceId = config("valecheck.pricing.subscriptions.{$plan}.stripe_price");

        if (empty($priceId)) {
            throw new InvalidArgumentException(
                "No Stripe price is configured for the [{$plan}] subscription plan. Set STRIPE_PRICE_".strtoupper($plan).' in .env.'
            );
        }

        return $user->newSubscription('default', $priceId)->checkout([
            'success_url' => route('dashboard').'?subscribed=1',
            'cancel_url' => route('dashboard'),
            'metadata' => [
                'kind' => 'subscription',
                'plan' => $plan,
            ],
        ]);
    }

    private function toMinorUnits(float $gross): int
    {
        return (int) round($gross * 100);
    }
}
