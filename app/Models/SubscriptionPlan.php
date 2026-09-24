<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * Centrally-configured, admin-manageable subscription plans (Pro/Dealer
 * tiers) - deliberately a database table, not config, so a new plan or a
 * price change never needs a deploy. group ('pro'/'dealer') is what
 * User::hasVerifiedTradeAccess() gates trade-restricted report content
 * on; sort_order defines both display order and upgrade ("higher") vs
 * downgrade ("lower") direction between plans.
 */
#[Fillable(['name', 'group', 'stripe_price_id', 'monthly_net', 'monthly_credits', 'additional_credit_net', 'is_active', 'sort_order'])]
class SubscriptionPlan extends Model
{
    public const GROUP_PRO = 'pro';

    public const GROUP_DEALER = 'dealer';

    protected function casts(): array
    {
        return [
            'monthly_net' => 'float',
            'additional_credit_net' => 'float',
            'is_active' => 'boolean',
        ];
    }

    public function isUpgradeFrom(self $current): bool
    {
        return $this->sort_order > $current->sort_order;
    }

    public function isDowngradeFrom(self $current): bool
    {
        return $this->sort_order < $current->sort_order;
    }
}
