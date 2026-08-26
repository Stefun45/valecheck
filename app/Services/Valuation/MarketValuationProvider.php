<?php

namespace App\Services\Valuation;

use App\DataTransferObjects\MarketValuation;
use App\DataTransferObjects\VehicleData;

interface MarketValuationProvider
{
    public function getValuation(VehicleData $vehicle): MarketValuation;
}
