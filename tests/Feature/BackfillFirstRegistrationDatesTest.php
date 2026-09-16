<?php

namespace Tests\Feature;

use App\Models\VehicleCheck;
use App\Models\VehicleHistory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BackfillFirstRegistrationDatesTest extends TestCase
{
    use RefreshDatabase;

    private function historyWithRawData(?array $dvsaVehicleData, ?string $firstRegistrationDate = null): VehicleHistory
    {
        return VehicleHistory::create([
            'vehicle_check_id' => VehicleCheck::factory()->create()->id,
            'first_registration_date' => $firstRegistrationDate,
            'raw_provider_data' => $dvsaVehicleData === null ? ['mot_and_tax' => ['dvsa_data' => []]] : [
                'mot_and_tax' => ['dvsa_data' => ['dvsa_vehicle_Data' => $dvsaVehicleData]],
            ],
        ]);
    }

    public function test_it_backfills_from_the_already_stored_raw_response(): void
    {
        $history = $this->historyWithRawData(['first_registration_date' => '2018-12-30']);

        $this->artisan('history:backfill-first-registration-dates')->assertSuccessful();

        $this->assertSame('2018-12-30', $history->fresh()->first_registration_date->toDateString());
    }

    public function test_it_leaves_a_record_untouched_when_the_raw_data_has_no_date_either(): void
    {
        $history = $this->historyWithRawData(['colour' => 'Blue']);

        $this->artisan('history:backfill-first-registration-dates');

        $this->assertNull($history->fresh()->first_registration_date);
    }

    public function test_it_never_overwrites_a_value_that_is_already_set(): void
    {
        $history = $this->historyWithRawData(['first_registration_date' => '2018-12-30'], firstRegistrationDate: '2099-01-01');

        $this->artisan('history:backfill-first-registration-dates');

        $this->assertSame('2099-01-01', $history->fresh()->first_registration_date->toDateString());
    }

    public function test_it_is_safe_to_run_more_than_once(): void
    {
        $history = $this->historyWithRawData(['first_registration_date' => '2018-12-30']);

        $this->artisan('history:backfill-first-registration-dates');
        $this->artisan('history:backfill-first-registration-dates')->assertSuccessful();

        $this->assertSame('2018-12-30', $history->fresh()->first_registration_date->toDateString());
    }

    public function test_a_record_with_no_raw_data_at_all_is_skipped_without_error(): void
    {
        $history = VehicleHistory::create([
            'vehicle_check_id' => VehicleCheck::factory()->create()->id,
        ]);

        $this->artisan('history:backfill-first-registration-dates')->assertSuccessful();

        $this->assertNull($history->fresh()->first_registration_date);
    }
}
