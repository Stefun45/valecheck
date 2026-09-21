<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'vehicle_check_id', 'write_off_category', 'write_off_date', 'first_registration_date', 'co2_gkm', 'damage_locations', 'finance_marker',
    'stolen_marker', 'high_risk_marker', 'scrapped_marker', 'imported', 'exported', 'was_exported', 'previous_keepers',
    'plate_changes', 'plate_change_history', 'colour_changes', 'vehicle_identity_checks', 'v5c_reissues', 'previous_searches',
    'vrm_matches', 'vin_matches', 'mileage_anomaly', 'mot_history', 'keeper_history',
    'raw_provider_data', 'confidence',
])]
class VehicleHistory extends Model
{
    /**
     * The official DVLA CO2 bands (A-M) introduced in March 2001 - shown
     * here purely as a visual reference for where this vehicle's own CO2
     * figure sits. Never used to derive a tax figure of our own: the real
     * VED rate already comes from One Auto's own dedicated tax endpoint
     * (see VehicleTaxCost).
     */
    private const CO2_BANDS = [
        ['letter' => 'A', 'max' => 100],
        ['letter' => 'B', 'max' => 110],
        ['letter' => 'C', 'max' => 120],
        ['letter' => 'D', 'max' => 130],
        ['letter' => 'E', 'max' => 140],
        ['letter' => 'F', 'max' => 150],
        ['letter' => 'G', 'max' => 165],
        ['letter' => 'H', 'max' => 175],
        ['letter' => 'I', 'max' => 185],
        ['letter' => 'J', 'max' => 200],
        ['letter' => 'K', 'max' => 225],
        ['letter' => 'L', 'max' => 255],
        ['letter' => 'M', 'max' => PHP_INT_MAX],
    ];

    protected function casts(): array
    {
        return [
            'write_off_date' => 'date',
            'first_registration_date' => 'date',
            'co2_gkm' => 'integer',
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

    /**
     * damage_locations is stored exactly as AutoCheck returns it — single
     * space-free words like "FrontNearside" — so this is display
     * formatting only, matching the same regex OneAutoMarketValuationProvider
     * uses to build the SalvageGuide primary_damage_desc parameter.
     *
     * @return string[]
     */
    public function formattedDamageLocations(): array
    {
        return array_map(
            fn (string $location) => trim(preg_replace('/(?<!^)([A-Z])/', ' $1', $location)),
            $this->damage_locations ?? [],
        );
    }

    public function vehicleCheck(): BelongsTo
    {
        return $this->belongsTo(VehicleCheck::class);
    }

    /**
     * @return array{letter: string, index: int}|null
     */
    public function co2Band(): ?array
    {
        if ($this->co2_gkm === null) {
            return null;
        }

        foreach (self::CO2_BANDS as $index => $band) {
            if ($this->co2_gkm <= $band['max']) {
                return ['letter' => $band['letter'], 'index' => $index];
            }
        }

        return null;
    }

    /**
     * One colour per band, green (lowest) through red (highest), for
     * drawing the full band strip alongside this vehicle's own position.
     *
     * @return string[]
     */
    public static function co2BandColours(): array
    {
        $count = count(self::CO2_BANDS);

        return array_map(
            fn (int $index) => self::bandColour($index, $count),
            array_keys(self::CO2_BANDS),
        );
    }

    private static function bandColour(int $index, int $count): string
    {
        $hue = 142 - (142 * ($index / ($count - 1)));

        return "hsl({$hue}, 62%, 42%)";
    }
}
