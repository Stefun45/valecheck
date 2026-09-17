<?php

namespace Tests\Unit;

use App\Models\SalvageAuctionCheck;
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

    public function test_a_same_day_fail_then_pass_retest_with_a_slightly_lower_reading_is_not_a_backwards_flag(): void
    {
        // Reproduces a real production report (PN66TCY): a failed MOT
        // fixed and retested the same day recorded 68,663 mi on the fail
        // and 68,664 mi on the pass — a 1-mile same-day reading
        // difference, not a genuine drop in mileage over time.
        $history = $this->historyFor([
            'mileage_anomaly' => false,
            'mot_history' => [
                ['test_date' => '2021-09-03', 'mileage' => 60519],
                ['test_date' => '2021-09-03', 'mileage' => 60519],
                ['test_date' => '2022-09-02', 'mileage' => 68664],
                ['test_date' => '2022-09-02', 'mileage' => 68663],
                ['test_date' => '2023-09-04', 'mileage' => 76575],
                ['test_date' => '2023-09-04', 'mileage' => 76575],
            ],
        ]);

        $this->assertTrue($this->boxesFor($history)['Mileage Trend']);
    }

    public function test_a_genuine_decrease_across_different_dates_still_warns(): void
    {
        $history = $this->historyFor([
            'mileage_anomaly' => false,
            'mot_history' => [
                ['test_date' => '2022-06-01', 'mileage' => 30000],
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

    public function test_verdict_is_clean_history_only_when_every_box_is_ok(): void
    {
        $history = $this->historyFor([
            'write_off_category' => null,
            'finance_marker' => false,
            'stolen_marker' => false,
            'mileage_anomaly' => false,
        ]);

        $verdict = ReportStatusSummary::verdict($history);

        $this->assertSame('Clean History', $verdict['label']);
        $this->assertSame('good', $verdict['tone']);
    }

    public function test_verdict_is_issues_found_when_any_box_is_not_ok(): void
    {
        $history = $this->historyFor(['finance_marker' => true]);

        $verdict = ReportStatusSummary::verdict($history);

        $this->assertSame('Issues Found', $verdict['label']);
        $this->assertSame('warning', $verdict['tone']);
    }

    public function test_verdict_is_a_distinct_unavailable_tone_never_folded_into_clean_or_warning(): void
    {
        $verdict = ReportStatusSummary::verdict(null);

        $this->assertSame('Unable to Verify', $verdict['label']);
        $this->assertSame('unavailable', $verdict['tone']);
    }

    private function allChecksFor(VehicleHistory $history, ?SalvageAuctionCheck $salvageCheck = null): array
    {
        return collect(ReportStatusSummary::allChecks($history, $salvageCheck))
            ->pluck('status', 'label')
            ->all();
    }

    public function test_all_checks_returns_an_empty_array_for_no_history(): void
    {
        $this->assertSame([], ReportStatusSummary::allChecks(null));
    }

    public function test_all_checks_maps_each_real_marker_to_pass_or_fail(): void
    {
        $history = $this->historyFor([
            'stolen_marker' => true,
            'finance_marker' => false,
            'write_off_category' => 'N',
            'scrapped_marker' => false,
            'imported' => true,
            'exported' => false,
        ]);

        $checks = $this->allChecksFor($history);

        $this->assertSame('fail', $checks['Stolen']);
        $this->assertSame('pass', $checks['Outstanding Finance']);
        $this->assertSame('fail', $checks['Written-Off']);
        $this->assertSame('pass', $checks['Scrapped']);
        $this->assertSame('fail', $checks['Imported']);
        $this->assertSame('pass', $checks['Exported']);
    }

    public function test_a_null_marker_is_unavailable_not_a_silent_pass(): void
    {
        $history = $this->historyFor(['stolen_marker' => null]);

        $this->assertSame('unavailable', $this->allChecksFor($history)['Stolen']);
    }

    public function test_mileage_issues_reuses_the_same_backwards_and_anomaly_logic_as_the_summary_boxes(): void
    {
        $wentBackwards = $this->historyFor([
            'mot_history' => [
                ['test_date' => '2022-01-01', 'mileage' => 30000],
                ['test_date' => '2023-01-01', 'mileage' => 25000],
            ],
        ]);
        $clean = $this->historyFor([
            'mot_history' => [
                ['test_date' => '2022-01-01', 'mileage' => 15000],
                ['test_date' => '2023-01-01', 'mileage' => 24000],
            ],
        ]);

        $this->assertSame('fail', $this->allChecksFor($wentBackwards)['Mileage Issues']);
        $this->assertSame('pass', $this->allChecksFor($clean)['Mileage Issues']);
    }

    public function test_salvage_history_row_is_omitted_entirely_when_no_salvage_check_is_given(): void
    {
        $history = $this->historyFor([]);

        $this->assertArrayNotHasKey('Salvage Auction History', $this->allChecksFor($history));
    }

    public function test_salvage_history_row_reflects_the_real_record_found_flag_when_given(): void
    {
        $history = $this->historyFor([]);
        $salvageCheck = SalvageAuctionCheck::create(['vehicle_check_id' => $history->vehicle_check_id, 'record_found' => true]);

        $this->assertSame('fail', $this->allChecksFor($history, $salvageCheck)['Salvage Auction History']);
    }
}
