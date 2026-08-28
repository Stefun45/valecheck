<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['vehicle_check_id', 'available', 'annual_rate', 'six_month_rate', 'tax_class'])]
class VehicleTaxCost extends Model
{
    protected function casts(): array
    {
        return [
            'available' => 'boolean',
            'annual_rate' => 'decimal:2',
            'six_month_rate' => 'decimal:2',
        ];
    }

    public function vehicleCheck(): BelongsTo
    {
        return $this->belongsTo(VehicleCheck::class);
    }
}
