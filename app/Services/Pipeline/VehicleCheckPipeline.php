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
use App\Jobs\RetrieveVehicleImage;
use App\Jobs\RetrieveVehicleTaxCost;
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
                new RetrieveVehicleImage($id),
                new RetrieveValuation($id),
                new AnalyseImages($id),
                new CalculateRepair($id),
                new CalculateMaximumBid($id),
                new CalculateDealScore($id),
                new GenerateReport($id),
            ],
            $vehicleCheck->needsValuation() => [
                new RetrieveVehicleHistory($id),
                new RetrieveVehicleImage($id),
                new RetrieveValuation($id),
                new RetrieveSalvageAuctionHistory($id),
                new RetrieveVehicleTaxCost($id),
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

    /**
     * A Check-to-Plus upgrade reuses the same VehicleCheck row and its
     * already-fetched history — RetrieveVehicleHistory must never run again
     * here, since that AutoCheck call is already paid for and its data is
     * still on the history() relation. Only the Plus-exclusive jobs run.
     */
    public function dispatchUpgrade(VehicleCheck $vehicleCheck): void
    {
        $id = $vehicleCheck->id;

        Bus::chain([
            new RetrieveVehicleImage($id),
            new RetrieveValuation($id),
            new RetrieveSalvageAuctionHistory($id),
            new RetrieveVehicleTaxCost($id),
            new GenerateReport($id),
        ])
            ->catch(function (Throwable $e) use ($id) {
                app(FailedVehicleCheckUpgradeHandler::class)->handle($id, $e->getMessage());
            })
            ->dispatch();
    }
}
