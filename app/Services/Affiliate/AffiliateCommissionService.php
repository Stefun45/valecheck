<?php

namespace App\Services\Affiliate;

/**
 * Configurable per-product/per-plan commission lookup — never hard-code a
 * commission amount at the call site. Actual commission creation (tied to a
 * confirmed Payment) is a fast-follow; this is the single source of truth
 * for "how much" once that's wired up.
 */
class AffiliateCommissionService
{
    public function commissionFor(string $productOrPlan): float
    {
        return (float) (config("valecheck.affiliate.commissions.{$productOrPlan}") ?? 0);
    }
}
