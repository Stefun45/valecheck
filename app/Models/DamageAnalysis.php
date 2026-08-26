<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['vehicle_check_id', 'summary', 'confidence', 'images_analysed'])]
class DamageAnalysis extends Model
{
    public function vehicleCheck(): BelongsTo
    {
        return $this->belongsTo(VehicleCheck::class);
    }

    public function findings(): HasMany
    {
        return $this->hasMany(DamageFinding::class);
    }
}
