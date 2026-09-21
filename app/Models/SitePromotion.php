<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * A single-row, admin-toggleable, site-wide launch discount - distinct
 * from DiscountCode (customer-typed) and UserProductPrice (per-account
 * bespoke override). Applies automatically to the standard price shown
 * to and charged from everyone, with no code to enter, so it can be
 * turned on for a launch push and off again with no deploy.
 */
#[Fillable(['is_active', 'percentage', 'label'])]
class SitePromotion extends Model
{
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'percentage' => 'decimal:2',
        ];
    }

    /**
     * The one row this table ever holds. Never null - the migration
     * seeds it inactive - but current()->isLive() is what callers should
     * actually branch on.
     */
    public static function current(): self
    {
        return static::query()->firstOrCreate([], ['is_active' => false, 'percentage' => 0]);
    }

    public function isLive(): bool
    {
        return $this->is_active && (float) $this->percentage > 0;
    }

    public function discount(float $gross): float
    {
        if (! $this->isLive()) {
            return $gross;
        }

        return round($gross * (1 - ((float) $this->percentage / 100)), 2);
    }
}
