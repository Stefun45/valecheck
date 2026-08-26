<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'type', 'report_type', 'amount', 'vehicle_check_id', 'payment_id', 'note'])]
class CreditTransaction extends Model
{
    public const TYPE_FREE_GRANT = 'free_grant';

    public const TYPE_PURCHASE = 'purchase';

    public const TYPE_CONSUMPTION = 'consumption';

    public const TYPE_REFUND = 'refund';

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
}
