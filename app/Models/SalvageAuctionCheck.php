<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['vehicle_check_id', 'record_found', 'records'])]
class SalvageAuctionCheck extends Model
{
    protected function casts(): array
    {
        return [
            'record_found' => 'boolean',
            'records' => 'array',
        ];
    }

    public function vehicleCheck(): BelongsTo
    {
        return $this->belongsTo(VehicleCheck::class);
    }
}
