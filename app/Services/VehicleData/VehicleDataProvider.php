<?php

namespace App\Services\VehicleData;

use App\DataTransferObjects\VehicleData;

interface VehicleDataProvider
{
    public function getVehicle(string $registration): VehicleData;
}
