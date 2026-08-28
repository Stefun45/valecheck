<?php

namespace App\Services\Pipeline;

use App\Jobs\AnalyseImages;
use App\Jobs\CalculateDealScore;
use App\Jobs\CalculateMaximumBid;
use App\Jobs\CalculateRepair;
use App\Jobs\GenerateReport;
use App\Jobs\RetrieveSalvageAuctionHistory;
use App\Jobs\RetrieveValuation;
use App\Jobs\RetrieveVehicleHistory;
use App\Models\VehicleCheck;
use Illuminate\Support\Facades\Bus;
use Throwable;

class VehicleCheckPipeline
{
    public function dispatch(VehicleCheck $vehicleCheck): void
    {
        $id = $vehicleCheck->id;

        $jobs = match (true) {
            $vehicleCheck->needsDamageAnalysis() => [
                new RetrieveVehicleHistory($id),
                new RetrieveValuation($id),
                new AnalyseImages($id),
                new CalculateRepair($id),
                new CalculateMaximumBid($id),
                new CalculateDealScore($id),
                new GenerateReport($id),
            ],
            $vehicleCheck->needsValuation() => [
                new RetrieveVehicleHistory($id),
                new RetrieveValuation($id),
                new RetrieveSalvageAuctionHistory($id),
                new GenerateReport($id),
            ],
            default => [
                new RetrieveVehicleHistory($id),
                new GenerateReport($id),
            ],
        };

        Bus::chain($jobs)
            ->catch(function (Throwable $e) use ($id) {
                app(FailedVehicleCheckHandler::class)->handle($id, $e->getMessage());
            })
            ->dispatch();
    }
}
