<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['registration', 'vin', 'make', 'model', 'derivative', 'year', 'engine', 'fuel', 'transmission', 'colour', 'specification'])]
class Vehicle extends Model
{
    use HasFactory;

    public function vehicleChecks(): HasMany
    {
        return $this->hasMany(VehicleCheck::class);
    }

    public function description(): string
    {
        return trim("{$this->year} {$this->make} {$this->model} {$this->derivative}");
    }

    /**
     * The full VIN is stored (needed for the provider lookup and any
     * future audit trail) but must never be shown to the customer —
     * reports only ever display the last 5 characters, matching standard
     * practice for vehicle-history products.
     */
    public function maskedVin(): ?string
    {
        if (! $this->vin) {
            return null;
        }

        return '••••••••••••'.substr($this->vin, -5);
    }
}
