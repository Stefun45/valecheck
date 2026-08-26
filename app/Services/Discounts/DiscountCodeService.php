<?php

namespace App\Services\Discounts;

use App\Models\DiscountCode;

class DiscountCodeService
{
    /**
     * Looks up a code and returns it only if it's genuinely usable right
     * now for this product — active, not expired, not exhausted, and
     * applicable to what's being bought. Never throws; an invalid code is
     * just "not found," so callers can fail gracefully.
     */
    public function find(string $code, string $productType): ?DiscountCode
    {
        $code = strtoupper(trim($code));

        if ($code === '') {
            return null;
        }

        $discount = DiscountCode::where('code', $code)->where('is_active', true)->first();

        if (! $discount) {
            return null;
        }

        if ($discount->expires_at && $discount->expires_at->isPast()) {
            return null;
        }

        if ($discount->max_redemptions !== null && $discount->times_redeemed >= $discount->max_redemptions) {
            return null;
        }

        if ($discount->applicable_products && ! in_array($productType, $discount->applicable_products, true)) {
            return null;
        }

        return $discount;
    }

    public function apply(DiscountCode $discount, float $gross): float
    {
        $discountAmount = $discount->type === DiscountCode::TYPE_PERCENTAGE
            ? $gross * ((float) $discount->value / 100)
            : (float) $discount->value;

        return max(0.0, round($gross - $discountAmount, 2));
    }
}
