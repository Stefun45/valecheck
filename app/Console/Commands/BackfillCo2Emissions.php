<?php

namespace App\Console\Commands;

use App\Models\VehicleHistory;
use Illuminate\Console\Command;

/**
 * One-off operational command: every VehicleHistory created before
 * OneAutoVehicleDataProvider started extracting co2_gkm already has it
 * sitting unused inside its own raw_provider_data - the raw AutoCheck
 * response was always stored, only the dedicated column is new.
 * Backfills from that existing data, so no re-fetch (and no repeat API
 * cost) is needed. Safe to re-run - only ever touches rows that are
 * still null.
 */
class BackfillCo2Emissions extends Command
{
    protected $signature = 'history:backfill-co2-emissions';

    protected $description = 'Backfill co2_gkm on existing VehicleHistory rows from their already-stored raw provider data.';

    public function handle(): int
    {
        $updated = 0;
        $skipped = 0;

        VehicleHistory::whereNull('co2_gkm')
            ->whereNotNull('raw_provider_data')
            ->chunkById(200, function ($histories) use (&$updated, &$skipped) {
                foreach ($histories as $history) {
                    $co2Gkm = $history->raw_provider_data['autocheck']['co2_gkm'] ?? null;

                    if ($co2Gkm === null) {
                        $skipped++;

                        continue;
                    }

                    $history->update(['co2_gkm' => $co2Gkm]);
                    $updated++;
                }
            });

        $this->info("Backfilled {$updated} record(s). Skipped {$skipped} with no co2_gkm in their raw data.");

        return self::SUCCESS;
    }
}
