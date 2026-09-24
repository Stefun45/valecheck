<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'type', 'report_type', 'amount', 'expires_at', 'subscription_plan_id', 'vehicle_check_id', 'payment_id', 'note'])]
class CreditTransaction extends Model
{
    public const TYPE_FREE_GRANT = 'free_grant';

    public const TYPE_PURCHASE = 'purchase';

    public const TYPE_CONSUMPTION = 'consumption';

    public const TYPE_REFUND = 'refund';

    /**
     * A subscription's monthly credit allocation - the one type that ever
     * sets expires_at, to the end of the billing period it was granted
     * for. Kept distinct from TYPE_PURCHASE so a plan's included
     * allowance is never conflated with a genuine one-off Stripe charge
     * in any report that groups by transaction type (same reasoning as
     * TYPE_FREE_GRANT already being kept distinct from TYPE_PURCHASE).
     */
    public const TYPE_SUBSCRIPTION_GRANT = 'subscription_grant';

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function vehicleCheck(): BelongsTo
    {
        return $this->belongsTo(VehicleCheck::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function subscriptionPlan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class);
    }
}
