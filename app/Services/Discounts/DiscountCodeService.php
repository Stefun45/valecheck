<?php

namespace App\Services\Discounts;

use App\Models\DiscountCode;
use App\Models\User;
use App\Models\UserProductPrice;

class DiscountCodeService
{
    /**
     * Looks up a code and returns it only if it's genuinely usable right
     * now for this product — active, not expired, not exhausted overall,
     * not exhausted for this specific user, applicable to what's being
     * bought, and not for an account that already has an admin-set custom
     * price for this product. Never throws; an invalid code is just "not
     * found," so callers can fail gracefully.
     *
     * The custom-price exclusion exists so the two discount mechanisms can
     * never compound — an account already paying a bespoke reduced price
     * must not also have a code's percentage/fixed reduction taken off
     * that already-reduced figure.
     *
     * $user is nullable because this is also called from the pre-checkout
     * preview, which can run before the customer has signed in — the
     * per-user limit and custom-price checks simply can't be done yet at
     * that point, so they're skipped there and re-checked for real once a
     * user exists, at StripeCheckoutService, which never trusts the
     * client-side preview.
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

        if ($user && UserProductPrice::where('user_id', $user->id)->where('type', $productType)->exists()) {
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
