<?php

namespace Tests\Unit;

use App\Models\VehicleCheck;
use App\Models\VehicleHistory;
use App\Services\Reports\VehicleTimeline;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VehicleTimelineTest extends TestCase
{
    use RefreshDatabase;

    private function historyFor(array $attributes): VehicleHistory
    {
        return VehicleHistory::create(array_merge(
            ['vehicle_check_id' => VehicleCheck::factory()->create()->id],
            $attributes,
        ));
    }

    public function test_it_returns_an_empty_array_for_no_history(): void
    {
        $this->assertSame([], VehicleTimeline::build(null));
    }

    public function test_it_merges_every_event_type_into_one_chronological_list(): void
    {
        $history = $this->historyFor([
            'first_registration_date' => '2018-01-10',
            'mot_history' => [
                ['test_date' => '2021-01-10', 'result' => 'PASSED', 'mileage' => 15000],
                ['test_date' => '2022-01-10', 'result' => 'FAILED', 'mileage' => 25000],
            ],
            'keeper_history' => [
                ['keeper_number' => 1, 'date_of_transfer' => '2019-06-01'],
            ],
            'plate_change_history' => [
                ['date' => '2020-03-01', 'from' => 'AB12CDE', 'to' => 'XY99ZZZ'],
            ],
            'write_off_category' => 'N',
            'write_off_date' => '2023-05-01',
        ]);

        $events = VehicleTimeline::build($history);

        $this->assertCount(6, $events);
        $this->assertSame([
            VehicleTimeline::TYPE_REGISTERED,
            VehicleTimeline::TYPE_KEEPER_CHANGE,
            VehicleTimeline::TYPE_PLATE_CHANGE,
            VehicleTimeline::TYPE_MOT_PASS,
            VehicleTimeline::TYPE_MOT_FAIL,
            VehicleTimeline::TYPE_WRITE_OFF,
        ], array_column($events, 'type'));

        // Chronological, not insertion order.
        $dates = array_map(fn ($e) => $e['date']->toDateString(), $events);
        $sorted = $dates;
        sort($sorted);
        $this->assertSame($sorted, $dates);
    }

    public function test_mot_pass_and_fail_are_distinguished_correctly(): void
    {
        $history = $this->historyFor([
            'mot_history' => [
                ['test_date' => '2021-01-10', 'result' => 'PASSED'],
                ['test_date' => '2022-01-10', 'result' => 'FAILED'],
            ],
        ]);

        $events = VehicleTimeline::build($history);

        $this->assertSame(VehicleTimeline::TYPE_MOT_PASS, $events[0]['type']);
        $this->assertSame(VehicleTimeline::TYPE_MOT_FAIL, $events[1]['type']);
    }

    public function test_the_write_off_label_includes_the_category_when_known(): void
    {
        $history = $this->historyFor(['write_off_category' => 'S', 'write_off_date' => '2023-05-01']);

        $events = VehicleTimeline::build($history);

        $this->assertSame('Recorded as a Category S write-off', $events[0]['label']);
    }

    public function test_events_with_no_date_are_skipped_rather_than_crashing(): void
    {
        $history = $this->historyFor([
            'mot_history' => [['result' => 'PASSED']],
            'keeper_history' => [['keeper_number' => 1]],
            'plate_change_history' => [['from' => 'AB12CDE', 'to' => 'XY99ZZZ']],
        ]);

        $this->assertSame([], VehicleTimeline::build($history));
    }

    public function test_aggregate_count_only_fields_never_produce_a_timeline_entry(): void
    {
        // colour_changes, v5c_reissues etc. are counts with no per-event
        // date — the timeline must never fabricate a dated entry for them.
        $history = $this->historyFor([
            'colour_changes' => 3,
            'v5c_reissues' => 2,
            'vehicle_identity_checks' => 1,
            'previous_searches' => 10,
        ]);

        $this->assertSame([], VehicleTimeline::build($history));
    }
}
