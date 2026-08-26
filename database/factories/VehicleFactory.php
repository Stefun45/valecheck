<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class VehicleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'registration' => strtoupper($this->faker->bothify('??##???')),
            'make' => 'Ford',
            'model' => 'Focus',
            'derivative' => 'ST-Line',
            'year' => $this->faker->numberBetween(2015, 2023),
            'engine' => '1.5 EcoBoost',
            'fuel' => 'Petrol',
            'transmission' => 'Manual',
            'colour' => 'Blue',
        ];
    }
}
