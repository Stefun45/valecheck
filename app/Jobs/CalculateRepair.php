<?php

namespace App\Jobs;

use App\Models\RepairEstimate;
use App\Models\VehicleCheck;
use App\Services\Repair\RepairEstimateService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class CalculateRepair implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public int $vehicleCheckId) {}

    public function handle(RepairEstimateService $service): void
    {
        $check = VehicleCheck::findOrFail($this->vehicleCheckId);
        $check->update(['stage' => 'calculating_repair']);

        $findings = $check->damageAnalysis?->findings->map->toData()->all() ?? [];

        $result = $service->estimate($findings);

        $estimate = RepairEstimate::updateOrCreate(
            ['vehicle_check_id' => $check->id],
            [
                'low_estimate' => $result->lowEstimate,
                'expected_estimate' => $result->expectedEstimate,
                'high_estimate' => $result->highEstimate,
                'assumptions_snapshot' => $result->assumptionsSnapshot,
            ]
        );

        $estimate->items()->delete();

        foreach ($result->items as $item) {
            $estimate->items()->create([
                'component' => $item->component,
                'action' => $item->action,
                'parts_cost' => $item->partsCost,
                'labour_hours' => $item->labourHours,
                'labour_cost' => $item->labourCost,
                'paint_cost' => $item->paintCost,
                'total_cost' => $item->totalCost,
            ]);
        }
    }
}
