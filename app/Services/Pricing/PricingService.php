<?php

namespace App\Services\Pricing;

use App\DataTransferObjects\PriceBreakdown;
use App\Models\ProductPrice;
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

    public function forCheck(): PriceBreakdown
    {
        return $this->forProduct('check');
    }

    public function forPlus(): PriceBreakdown
    {
        return $this->forProduct('plus');
    }

    public function forRebuild(): PriceBreakdown
    {
        return $this->forProduct('rebuild');
    }

    public function forProduct(string $type): PriceBreakdown
    {
        $gross = ProductPrice::where('type', $type)->value('gross');

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

        if (isset($subscription['gross'])) {
            return $this->breakdown((float) $subscription['gross']);
        }

        $reportType = array_key_first($subscription['allowances']);
        $credits = $subscription['allowances'][$reportType];
        $unitGross = $this->forProduct($reportType)->gross;

        return $this->breakdown($unitGross * $credits * (1 - ($subscription['discount'] ?? 0)));
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
