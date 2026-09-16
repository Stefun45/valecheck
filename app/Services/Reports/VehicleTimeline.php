<?php

namespace App\Services\Reports;

use App\Models\VehicleHistory;
use Illuminate\Support\Carbon;

/**
 * Merges every genuinely dated event ValeCheck has for a vehicle — first
 * registration, each MOT test, each keeper change, each plate change, and
 * a write-off — into one chronological narrative. Deliberately does NOT
 * include anything that's only an aggregate count with no per-event date
 * (colour changes, V5C reissues, vehicle identity checks, previous
 * searches) — those stay in their existing "Keeper / Registration
 * History" card rather than being fabricated as a dated timeline entry.
 *
 * Additive only — every existing section (MOT table, keeper facts, plate
 * changes, write-off) stays exactly as it is; this is a second, narrative
 * view of the same underlying data, not a replacement.
 */
class VehicleTimeline
{
    public const TYPE_REGISTERED = 'registered';

    public const TYPE_MOT_PASS = 'mot_pass';

    public const TYPE_MOT_FAIL = 'mot_fail';

    public const TYPE_KEEPER_CHANGE = 'keeper_change';

    public const TYPE_PLATE_CHANGE = 'plate_change';

    public const TYPE_WRITE_OFF = 'write_off';

    /**
     * @return array<int, array{date: Carbon, type: string, label: string, detail: ?string}>
     */
    public static function build(?VehicleHistory $history): array
    {
        if (! $history) {
            return [];
        }

        $events = [];

        if ($history->first_registration_date) {
            $events[] = [
                'date' => Carbon::parse($history->first_registration_date),
                'type' => self::TYPE_REGISTERED,
                'label' => 'First registered',
                'detail' => null,
            ];
        }

        foreach ($history->mot_history ?? [] as $test) {
            if (empty($test['test_date'])) {
                continue;
            }

            $failed = str_contains(strtolower((string) ($test['result'] ?? '')), 'fail');

            $events[] = [
                'date' => Carbon::parse($test['test_date']),
                'type' => $failed ? self::TYPE_MOT_FAIL : self::TYPE_MOT_PASS,
                'label' => $failed ? 'MOT failed' : 'MOT passed',
                'detail' => isset($test['mileage']) ? number_format((int) $test['mileage']).' mi' : null,
            ];
        }

        foreach ($history->keeper_history ?? [] as $change) {
            if (empty($change['date_of_transfer'])) {
                continue;
            }

            $events[] = [
                'date' => Carbon::parse($change['date_of_transfer']),
                'type' => self::TYPE_KEEPER_CHANGE,
                'label' => 'Registered keeper changed',
                'detail' => null,
            ];
        }

        foreach ($history->plate_change_history ?? [] as $change) {
            if (empty($change['date'])) {
                continue;
            }

            $events[] = [
                'date' => Carbon::parse($change['date']),
                'type' => self::TYPE_PLATE_CHANGE,
                'label' => 'Number plate changed',
                'detail' => (! empty($change['from']) && ! empty($change['to'])) ? "{$change['from']} \u{2192} {$change['to']}" : null,
            ];
        }

        if ($history->write_off_date) {
            $events[] = [
                'date' => Carbon::parse($history->write_off_date),
                'type' => self::TYPE_WRITE_OFF,
                'label' => $history->write_off_category
                    ? "Recorded as a Category {$history->write_off_category} write-off"
                    : 'Recorded as a write-off',
                'detail' => null,
            ];
        }

        usort($events, fn (array $a, array $b) => $a['date']->timestamp <=> $b['date']->timestamp);

        return $events;
    }
}
