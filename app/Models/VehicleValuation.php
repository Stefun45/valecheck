<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'vehicle_check_id', 'clean_value', 'trade_value', 'retail_value', 'private_value',
    'salvage_adjusted_value', 'write_off_category_applied', 'discount_applied',
    'comparables', 'confidence',
])]
class VehicleValuation extends Model
{
    protected function casts(): array
    {
        return [
            'clean_value' => 'decimal:2',
            'trade_value' => 'decimal:2',
            'retail_value' => 'decimal:2',
            'private_value' => 'decimal:2',
            'salvage_adjusted_value' => 'decimal:2',
            'discount_applied' => 'decimal:4',
            'comparables' => 'array',
        ];
    }

    public function vehicleCheck(): BelongsTo
    {
        return $this->belongsTo(VehicleCheck::class);
    }
}
