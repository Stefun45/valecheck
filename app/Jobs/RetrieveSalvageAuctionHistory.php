<?php

namespace App\Jobs;

use App\Models\SalvageAuctionCheck;
use App\Models\VehicleCheck;
use App\Services\SalvageAuction\SalvageAuctionProvider;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * ValeCheck Plus only — has this vehicle previously been listed at a
 * salvage/insurance auction. A failed lookup here must never fail the
 * whole report (see OneAutoSalvageAuctionProvider), so this job has no
 * special failure handling of its own — a provider-level exception is
 * genuinely unexpected and should surface like any other pipeline failure.
 */
class RetrieveSalvageAuctionHistory implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public array $backoff = [10, 30, 60];

    public function __construct(public int $vehicleCheckId) {}

    public function handle(SalvageAuctionProvider $provider): void
    {
        $check = VehicleCheck::findOrFail($this->vehicleCheckId);
        $check->update(['stage' => 'retrieving_salvage_auction_history']);

        $data = $provider->check($check->registration, $check->id);

        SalvageAuctionCheck::updateOrCreate(
            ['vehicle_check_id' => $check->id],
            [
                'record_found' => $data->recordFound,
                'records' => $data->records,
            ]
        );
    }
}
