<?php

namespace Tests\Feature;

use App\Models\VehicleCheck;
use App\Models\VehicleHistory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BackfillCo2EmissionsTest extends TestCase
{
    use RefreshDatabase;

    private function historyWithRawData(?int $rawCo2Gkm, ?int $co2Gkm = null): VehicleHistory
    {
        return VehicleHistory::create([
            'vehicle_check_id' => VehicleCheck::factory()->create()->id,
            'co2_gkm' => $co2Gkm,
            'raw_provider_data' => $rawCo2Gkm === null ? ['autocheck' => []] : [
                'autocheck' => ['co2_gkm' => $rawCo2Gkm],
            ],
        ]);
    }

    public function test_it_backfills_from_the_already_stored_raw_response(): void
    {
        $history = $this->historyWithRawData(139);

        $this->artisan('history:backfill-co2-emissions')->assertSuccessful();

        $this->assertSame(139, $history->fresh()->co2_gkm);
    }

    public function test_it_leaves_a_record_untouched_when_the_raw_data_has_no_co2_either(): void
    {
        $history = $this->historyWithRawData(null);

        $this->artisan('history:backfill-co2-emissions');

        $this->assertNull($history->fresh()->co2_gkm);
    }

    public function test_it_never_overwrites_a_value_that_is_already_set(): void
    {
        $history = $this->historyWithRawData(139, co2Gkm: 999);

        $this->artisan('history:backfill-co2-emissions');

        $this->assertSame(999, $history->fresh()->co2_gkm);
    }

    public function test_it_is_safe_to_run_more_than_once(): void
    {
        $history = $this->historyWithRawData(139);

        $this->artisan('history:backfill-co2-emissions');
        $this->artisan('history:backfill-co2-emissions')->assertSuccessful();

        $this->assertSame(139, $history->fresh()->co2_gkm);
    }

    public function test_a_record_with_no_raw_data_at_all_is_skipped_without_error(): void
    {
        $history = VehicleHistory::create([
            'vehicle_check_id' => VehicleCheck::factory()->create()->id,
        ]);

        $this->artisan('history:backfill-co2-emissions')->assertSuccessful();

        $this->assertNull($history->fresh()->co2_gkm);
    }
}
