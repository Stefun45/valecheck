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
}
