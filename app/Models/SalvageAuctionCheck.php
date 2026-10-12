<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    /**
     * Ordered by which record (in the records JSON array) an image belongs
     * to, then by its display position within that record.
     */
    public function images(): HasMany
    {
        return $this->hasMany(SalvageAuctionImage::class)->orderBy('record_index')->orderBy('position');
    }
}
