<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * One row per product type ('check', 'plus', 'rebuild', 'plus_upgrade'),
 * each an admin-toggleable, explicit discounted price - distinct from
 * DiscountCode (customer-typed) and UserProductPrice (per-account
 * bespoke override). An admin sets a real "was £X now £Y" price for
 * whichever plans they choose, with no code to enter, so it can be
 * turned on for a launch push and off again with no deploy.
 */
#[Fillable(['type', 'is_active', 'discounted_gross'])]
class SitePromotion extends Model
{
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'discounted_gross' => 'decimal:2',
        ];
    }

    public static function current(string $type): self
    {
        return static::query()->firstOrCreate(['type' => $type], ['is_active' => false, 'discounted_gross' => null]);
    }

    public function isLive(): bool
    {
        return $this->is_active && $this->discounted_gross !== null;
    }

    public function discount(float $standardGross): float
    {
        return $this->isLive() ? (float) $this->discounted_gross : $standardGross;
    }
}
