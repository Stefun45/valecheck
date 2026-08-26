<?php

namespace App\Services\Pricing;

use App\DataTransferObjects\PriceBreakdown;
use InvalidArgumentException;

/**
 * Single source of truth for VAT-inclusive pricing. All prices in
 * config('valecheck.pricing') are GROSS (VAT inclusive) — this service is
 * the only place that derives net/VAT figures from them. Nothing else in
 * the codebase should compute VAT directly.
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
        return $this->breakdown((float) config('valecheck.pricing.check.gross'));
    }

    public function forPlus(): PriceBreakdown
    {
        return $this->breakdown((float) config('valecheck.pricing.plus.gross'));
    }

    public function forRebuild(): PriceBreakdown
    {
        return $this->breakdown((float) config('valecheck.pricing.rebuild.gross'));
    }

    public function forProduct(string $type): PriceBreakdown
    {
        return $this->breakdown((float) config("valecheck.pricing.{$type}.gross"));
    }

    public function forCreditPack(string $key): PriceBreakdown
    {
        $pack = config("valecheck.pricing.credit_packs.{$key}");

        if (! $pack) {
            throw new InvalidArgumentException("Unknown credit pack [{$key}].");
        }

        return $this->breakdown((float) $pack['gross']);
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
