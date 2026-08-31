<?php

namespace Tests\Unit;

use App\Models\VehicleCheck;
use App\Models\VehicleHistory;
use App\Services\Reports\ReportStatusSummary;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportStatusSummaryTest extends TestCase
{
    use RefreshDatabase;

    private function boxesFor(VehicleHistory $history): array
    {
        return collect(ReportStatusSummary::forHistory($history))
            ->pluck('ok', 'label')
            ->all();
    }

    private function historyFor(array $attributes): VehicleHistory
    {
        return VehicleHistory::create(array_merge(
            ['vehicle_check_id' => VehicleCheck::factory()->create()->id],
            $attributes,
        ));
    }

    public function test_a_clean_history_with_a_rising_mileage_trend_is_all_green(): void
    {
        $history = $this->historyFor([
            'write_off_category' => null,
            'finance_marker' => false,
            'stolen_marker' => false,
            'mileage_anomaly' => false,
            'mot_history' => [
                ['test_date' => '2022-06-01', 'mileage' => 15000],
                ['test_date' => '2023-06-01', 'mileage' => 24000],
            ],
        ]);

        $boxes = $this->boxesFor($history);

        $this->assertTrue($boxes['Mileage Trend']);
        $this->assertTrue($boxes['Write-Off History']);
        $this->assertTrue($boxes['Finance']);
        $this->assertTrue($boxes['Stolen']);
    }

    public function test_mileage_going_backwards_between_tests_warns_even_without_the_providers_own_anomaly_flag(): void
    {
        $history = $this->historyFor([
            'mileage_anomaly' => false,
            'mot_history' => [
                ['test_date' => '2022-06-01', 'mileage' => 30000],
                ['test_date' => '2023-06-01', 'mileage' => 25000],
            ],
        ]);

        $this->assertFalse($this->boxesFor($history)['Mileage Trend']);
    }

    public function test_write_off_finance_and_stolen_markers_each_warn_independently(): void
    {
        $writeOff = $this->historyFor(['write_off_category' => 'N']);
        $finance = $this->historyFor(['finance_marker' => true]);
        $stolen = $this->historyFor(['stolen_marker' => true]);

        $this->assertFalse($this->boxesFor($writeOff)['Write-Off History']);
        $this->assertFalse($this->boxesFor($finance)['Finance']);
        $this->assertFalse($this->boxesFor($stolen)['Stolen']);
    }

    public function test_a_missing_history_record_warns_on_every_box_rather_than_defaulting_to_green(): void
    {
        // A failed lookup must never look identical to "we checked and it's
        // fine" — this is the one case the whole feature exists to avoid.
        $boxes = collect(ReportStatusSummary::forHistory(null))->pluck('ok', 'label')->all();

        $this->assertFalse($boxes['Mileage Trend']);
        $this->assertFalse($boxes['Write-Off History']);
        $this->assertFalse($boxes['Finance']);
        $this->assertFalse($boxes['Stolen']);
    }
}
