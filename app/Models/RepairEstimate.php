<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['vehicle_check_id', 'low_estimate', 'expected_estimate', 'high_estimate', 'assumptions_snapshot'])]
class RepairEstimate extends Model
{
    protected function casts(): array
    {
        return [
            'low_estimate' => 'decimal:2',
            'expected_estimate' => 'decimal:2',
            'high_estimate' => 'decimal:2',
            'assumptions_snapshot' => 'array',
        ];
    }

    public function vehicleCheck(): BelongsTo
    {
        return $this->belongsTo(VehicleCheck::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(RepairItem::class);
    }
}
