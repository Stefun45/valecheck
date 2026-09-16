<?php

namespace App\Services\Discounts;

use App\Models\DiscountCode;
use App\Models\User;

class DiscountCodeService
{
    /**
     * Looks up a code and returns it only if it's genuinely usable right
     * now for this product — active, not expired, not exhausted overall,
     * not exhausted for this specific user, and applicable to what's being
     * bought. Never throws; an invalid code is just "not found," so callers
     * can fail gracefully.
     *
     * $user is nullable because this is also called from the pre-checkout
     * preview, which can run before the customer has signed in — the
     * per-user limit simply can't be checked yet at that point, so it's
     * skipped there and re-checked for real once a user exists, at
     * StripeCheckoutService, which never trusts the client-side preview.
     */
    public function find(string $code, string $productType, ?User $user = null): ?DiscountCode
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

        if ($discount->max_uses_per_user !== null && $user) {
            $usesByThisUser = $discount->redemptions()->where('user_id', $user->id)->count();

            if ($usesByThisUser >= $discount->max_uses_per_user) {
                return null;
            }
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
