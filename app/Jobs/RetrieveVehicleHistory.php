<?php

namespace App\Jobs;

use App\Models\VehicleCheck;
use App\Models\VehicleHistory;
use App\Services\VehicleData\VehicleDataProvider;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RetrieveVehicleHistory implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public array $backoff = [10, 30, 60];

    public function __construct(public int $vehicleCheckId) {}

    public function handle(VehicleDataProvider $provider): void
    {
        $check = VehicleCheck::findOrFail($this->vehicleCheckId);

        $check->update([
            'status' => VehicleCheck::STATUS_PROCESSING,
            'stage' => 'retrieving_history',
            'started_at' => $check->started_at ?? now(),
        ]);

        $data = $provider->getVehicle($check->registration, $check->id);

        $check->vehicle->update([
            'vin' => $data->vin,
            'make' => $data->make,
            'model' => $data->model,
            'derivative' => $data->derivative,
            'year' => $data->year,
            'engine' => $data->engine,
            'fuel' => $data->fuel,
            'transmission' => $data->transmission,
            'colour' => $data->colour,
            'specification' => $data->specification,
        ]);

        VehicleHistory::updateOrCreate(
            ['vehicle_check_id' => $check->id],
            [
                'write_off_category' => $data->writeOffCategory,
                'write_off_date' => $data->writeOffDate,
                'finance_marker' => $data->financeMarker,
                'stolen_marker' => $data->stolenMarker,
                'high_risk_marker' => $data->highRiskMarker,
                'scrapped_marker' => $data->scrappedMarker,
                'imported' => $data->imported,
                'exported' => $data->exported,
                'was_exported' => $data->wasExported,
                'previous_keepers' => $data->previousKeepers,
                'plate_changes' => $data->plateChanges,
                'colour_changes' => $data->colourChanges,
                'vehicle_identity_checks' => $data->vehicleIdentityChecks,
                'v5c_reissues' => $data->v5cReissues,
                'previous_searches' => $data->previousSearches,
                'vrm_matches' => $data->vrmMatches,
                'vin_matches' => $data->vinMatches,
                'mileage_anomaly' => $data->mileageAnomaly,
                'mot_history' => $data->motHistory,
                'keeper_history' => $data->keeperHistory,
                'raw_provider_data' => $data->raw,
                'confidence' => $data->confidence,
            ]
        );
    }
}
