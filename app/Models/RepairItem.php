<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['repair_estimate_id', 'component', 'action', 'parts_cost', 'labour_hours', 'labour_cost', 'paint_cost', 'total_cost'])]
class RepairItem extends Model
{
    protected function casts(): array
    {
        return [
            'parts_cost' => 'decimal:2',
            'labour_hours' => 'decimal:2',
            'labour_cost' => 'decimal:2',
            'paint_cost' => 'decimal:2',
            'total_cost' => 'decimal:2',
        ];
    }

    public function repairEstimate(): BelongsTo
    {
        return $this->belongsTo(RepairEstimate::class);
    }
}
