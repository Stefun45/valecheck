<?php

namespace App\Services\Pricing;

use App\DataTransferObjects\PriceBreakdown;
use App\Models\ProductPrice;
use App\Models\User;
use App\Models\UserProductPrice;
use InvalidArgumentException;

/**
 * Single source of truth for VAT-inclusive pricing. Check/Plus/Rebuild's
 * gross prices live in the product_prices table (editable from the admin
 * pricing screen) — config('valecheck.pricing.*.gross') is only consulted
 * as a fallback if a product's row is somehow missing. Credit packs derive
 * their price from the relevant product's gross price at a config-defined
 * discount, so they move automatically with an admin price change.
 * Subscription plans are still config-only (unedited by this admin screen).
 * This service is the only place that derives net/VAT figures from a
 * gross price — nothing else in the codebase should compute VAT directly.
 *
 * Any product lookup can optionally be scoped to a specific user — an
 * admin-set UserProductPrice row for that user+type takes priority over
 * the standard price everyone else pays. Passing no user (or a user with
 * no override) falls through to standard pricing exactly as before.
 */
class PricingService
{
    public function vatRate(): float
    {
        return (float) config('valecheck.vat.rate');
    }

    public function breakdown(float $gross): PriceBreakdown
    {
        $rate = $this->vatRate();
        $net = round($gross / (1 + $rate), 2);
        $vat = round($gross - $net, 2);

        return new PriceBreakdown(
            gross: round($gross, 2),
            net: $net,
            vat: $vat,
            vatRate: $rate,
            currency: config('valecheck.currency'),
        );
    }

    public function forCheck(?User $user = null): PriceBreakdown
    {
        return $this->forProduct('check', $user);
    }

    public function forPlus(?User $user = null): PriceBreakdown
    {
        return $this->forProduct('plus', $user);
    }

    public function forRebuild(?User $user = null): PriceBreakdown
    {
        return $this->forProduct('rebuild', $user);
    }

    public function forProduct(string $type, ?User $user = null): PriceBreakdown
    {
        $gross = $user
            ? UserProductPrice::where('user_id', $user->id)->where('type', $type)->value('gross')
            : null;

        $gross ??= ProductPrice::where('type', $type)->value('gross');

        return $this->breakdown((float) ($gross ?? config("valecheck.pricing.{$type}.gross")));
    }

    public function forCreditPack(string $key): PriceBreakdown
    {
        $pack = config("valecheck.pricing.credit_packs.{$key}");

        if (! $pack) {
            throw new InvalidArgumentException("Unknown credit pack [{$key}].");
        }

        $unitGross = $this->forProduct($pack['report_type'])->gross;

        return $this->breakdown($unitGross * $pack['credits'] * (1 - ($pack['discount'] ?? 0)));
    }

    public function forSubscription(string $plan): PriceBreakdown
    {
        $subscription = config("valecheck.pricing.subscriptions.{$plan}");

        if (! $subscription) {
            throw new InvalidArgumentException("Unknown subscription plan [{$plan}].");
        }

        return $this->breakdown((float) $subscription['gross']);
    }

    public function creditPackCredits(string $key): int
    {
        $pack = config("valecheck.pricing.credit_packs.{$key}");

        if (! $pack) {
            throw new InvalidArgumentException("Unknown credit pack [{$key}].");
        }

        return (int) $pack['credits'];
    }
}
