<?php

namespace App\Jobs;

use App\Models\VehicleCheck;
use App\Models\VehicleTaxCost;
use App\Services\VehicleTax\VehicleTaxCostProvider;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * ValeCheck Plus only — the real cost to tax this specific vehicle (One
 * Auto's Vehicle Tax from VRM, 10p/call). Additive, not safety-critical, so
 * an unavailable result is stored as such rather than failing the report.
 */
class RetrieveVehicleTaxCost implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public array $backoff = [10, 30, 60];

    public function __construct(public int $vehicleCheckId) {}

    public function handle(VehicleTaxCostProvider $provider): void
    {
        $check = VehicleCheck::findOrFail($this->vehicleCheckId);
        $check->update(['stage' => 'retrieving_tax_cost']);

        $data = $provider->check($check->registration, $check->id);

        VehicleTaxCost::updateOrCreate(
            ['vehicle_check_id' => $check->id],
            [
                'available' => $data->available,
                'annual_rate' => $data->annualRate,
                'six_month_rate' => $data->sixMonthRate,
                'tax_class' => $data->taxClass,
            ]
        );
    }
}
