<?php

namespace App\Console\Commands;

use App\Models\VehicleHistory;
use Illuminate\Console\Command;

/**
 * One-off operational command: every VehicleHistory created before
 * OneAutoVehicleDataProvider started extracting first_registration_date
 * already has it sitting unused inside its own raw_provider_data —
 * the raw MOT/Tax response was always stored, only the dedicated column
 * is new. Backfills from that existing data, so no re-fetch (and no
 * repeat API cost) is needed. Safe to re-run — only ever touches rows
 * that are still null.
 */
class BackfillFirstRegistrationDates extends Command
{
    protected $signature = 'history:backfill-first-registration-dates';

    protected $description = 'Backfill first_registration_date on existing VehicleHistory rows from their already-stored raw provider data.';

    public function handle(): int
    {
        $updated = 0;
        $skipped = 0;

        VehicleHistory::whereNull('first_registration_date')
            ->whereNotNull('raw_provider_data')
            ->chunkById(200, function ($histories) use (&$updated, &$skipped) {
                foreach ($histories as $history) {
                    $date = $history->raw_provider_data['mot_and_tax']['dvsa_data']['dvsa_vehicle_Data']['first_registration_date'] ?? null;

                    if ($date === null) {
                        $skipped++;

                        continue;
                    }

                    $history->update(['first_registration_date' => $date]);
                    $updated++;
                }
            });

        $this->info("Backfilled {$updated} record(s). Skipped {$skipped} with no first_registration_date in their raw data.");

        return self::SUCCESS;
    }
}
