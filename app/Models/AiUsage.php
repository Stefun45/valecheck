<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'vehicle_check_id', 'provider', 'model', 'stage', 'input_tokens', 'output_tokens',
    'image_count', 'estimated_cost', 'actual_cost', 'duration_ms', 'success', 'error_message',
])]
class AiUsage extends Model
{
    protected function casts(): array
    {
        return [
            'estimated_cost' => 'decimal:4',
            'actual_cost' => 'decimal:4',
            'success' => 'boolean',
        ];
    }

    public function vehicleCheck(): BelongsTo
    {
        return $this->belongsTo(VehicleCheck::class);
    }
}
