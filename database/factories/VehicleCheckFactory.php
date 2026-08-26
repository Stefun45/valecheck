<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleCheck;
use Illuminate\Database\Eloquent\Factories\Factory;

class VehicleCheckFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'vehicle_id' => Vehicle::factory(),
            'type' => VehicleCheck::TYPE_REBUILD,
            'status' => VehicleCheck::STATUS_PENDING,
            'funding_source' => 'purchase',
            'registration' => strtoupper($this->faker->bothify('??##???')),
        ];
    }
}
