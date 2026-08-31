<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'vehicle_check_id', 'write_off_category', 'write_off_date', 'damage_locations', 'finance_marker',
    'stolen_marker', 'high_risk_marker', 'scrapped_marker', 'imported', 'exported', 'was_exported', 'previous_keepers',
    'plate_changes', 'plate_change_history', 'colour_changes', 'vehicle_identity_checks', 'v5c_reissues', 'previous_searches',
    'vrm_matches', 'vin_matches', 'mileage_anomaly', 'mot_history', 'keeper_history',
    'raw_provider_data', 'confidence',
])]
class VehicleHistory extends Model
{
    protected function casts(): array
    {
        return [
            'write_off_date' => 'date',
            'damage_locations' => 'array',
            'finance_marker' => 'boolean',
            'stolen_marker' => 'boolean',
            'high_risk_marker' => 'boolean',
            'scrapped_marker' => 'boolean',
            'imported' => 'boolean',
            'exported' => 'boolean',
            'was_exported' => 'boolean',
            'vrm_matches' => 'boolean',
            'vin_matches' => 'boolean',
            'mileage_anomaly' => 'boolean',
            'mot_history' => 'array',
            'keeper_history' => 'array',
            'plate_change_history' => 'array',
            'raw_provider_data' => 'array',
        ];
    }

    public function isWrittenOff(): bool
    {
        return ! is_null($this->write_off_category) && $this->write_off_category !== 'none';
    }

    public function vehicleCheck(): BelongsTo
    {
        return $this->belongsTo(VehicleCheck::class);
    }
}
