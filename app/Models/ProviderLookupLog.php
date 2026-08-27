<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One row per vehicle-data-provider API call (success or failure) — the
 * admin-visible record of what was actually called, when, and whether it
 * worked. Never stores the API key or a raw response body.
 */
#[Fillable([
    'provider', 'endpoint', 'registration', 'vehicle_check_id',
    'status', 'http_status', 'error_message',
])]
class ProviderLookupLog extends Model
{
    public const STATUS_SUCCESS = 'success';

    public const STATUS_FAILED = 'failed';

    public function vehicleCheck(): BelongsTo
    {
        return $this->belongsTo(VehicleCheck::class);
    }
}
