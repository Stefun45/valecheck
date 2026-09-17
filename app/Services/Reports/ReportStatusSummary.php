<?php

namespace App\Services\Reports;

use App\Models\SalvageAuctionCheck;
use App\Models\VehicleHistory;

/**
 * The four "at a glance" trust signals shown at the top of Check/Plus
 * reports. Computed once here — the web and PDF templates render this with
 * completely different markup (Tailwind grid vs. a dompdf-compatible
 * table), so the underlying decision logic must live in exactly one place
 * or the two can silently drift apart.
 *
 * A missing history record (lookup failed) never shows as a false "all
 * clear" — every box goes to warn rather than defaulting to ok, since
 * "we don't know" must never look identical to "we checked and it's fine."
 */
class ReportStatusSummary
{
    /**
     * @return array<int, array{label: string, ok: bool}>
     */
    public static function forHistory(?VehicleHistory $history): array
    {
        return [
            ['label' => 'Mileage Trend', 'ok' => $history !== null && ! self::mileageWentBackwards($history) && ! $history->mileage_anomaly],
            ['label' => 'Write-Off History', 'ok' => $history !== null && ! $history->isWrittenOff()],
            ['label' => 'Finance', 'ok' => $history !== null && ! $history->finance_marker],
            ['label' => 'Stolen', 'ok' => $history !== null && ! $history->stolen_marker],
        ];
    }

    /**
     * A single headline verdict derived from the same four boxes above —
     * deliberately not a separate scoring system, so it can never say
     * something the boxes underneath it don't already back up. A history
     * lookup failure is its own distinct "unavailable" tone, never folded
     * into either "clean" or "issues found".
     *
     * @return array{label: string, tone: 'good'|'warning'|'unavailable'}
     */
    public static function verdict(?VehicleHistory $history): array
    {
        if ($history === null) {
            return ['label' => 'Unable to Verify', 'tone' => 'unavailable'];
        }

        $allOk = collect(self::forHistory($history))->every(fn (array $box) => $box['ok']);

        return $allOk
            ? ['label' => 'Clean History', 'tone' => 'good']
            : ['label' => 'Issues Found', 'tone' => 'warning'];
    }

    /**
     * A longer, explicit pass/fail checklist alongside the four headline
     * boxes above — every row here maps to a field genuinely captured by
     * the provider, nothing is inferred or guessed. $salvageCheck is
     * omitted entirely (not shown as "unavailable") for a Check-type
     * report, since that product never attempts a salvage auction lookup
     * at all — that's a difference in what was bought, not missing data.
     *
     * @return array<int, array{label: string, status: 'pass'|'fail'|'unavailable'}>
     */
    public static function allChecks(?VehicleHistory $history, ?SalvageAuctionCheck $salvageCheck = null): array
    {
        if ($history === null) {
            return [];
        }

        $checks = [
            ['label' => 'Stolen', 'status' => self::badStatus($history->stolen_marker)],
            ['label' => 'Outstanding Finance', 'status' => self::badStatus($history->finance_marker)],
            ['label' => 'Written-Off', 'status' => self::badStatus($history->isWrittenOff())],
            ['label' => 'Scrapped', 'status' => self::badStatus($history->scrapped_marker)],
            ['label' => 'Imported', 'status' => self::badStatus($history->imported)],
            ['label' => 'Exported', 'status' => self::badStatus($history->exported)],
            ['label' => 'Mileage Issues', 'status' => self::badStatus(self::mileageWentBackwards($history) || ($history->mileage_anomaly === true))],
        ];

        if ($salvageCheck !== null) {
            $checks[] = ['label' => 'Salvage Auction History', 'status' => self::badStatus($salvageCheck->record_found)];
        }

        return $checks;
    }

    /**
     * $isBad === null means the provider didn't return this section at all
     * — kept as its own distinct "unavailable" state rather than being
     * read as a pass, for the same reason a missing history record is
     * never shown as a clean report.
     */
    private static function badStatus(?bool $isBad): string
    {
        if ($isBad === null) {
            return 'unavailable';
        }

        return $isBad ? 'fail' : 'pass';
    }

    private static function mileageWentBackwards(VehicleHistory $history): bool
    {
        // A failed MOT that's fixed and retested the same day is recorded
        // as two separate entries with the same test_date, and the two
        // odometer readings routinely differ by a mile or two (reading
        // noise, or the car being driven to/from the test bay) — that is
        // not a genuine drop in mileage over time, so same-day entries are
        // collapsed to a single reading (the highest recorded that day)
        // before comparing consecutive dates.
        $mileageByDate = collect($history->mot_history ?? [])
            ->filter(fn ($test) => isset($test['mileage'], $test['test_date']))
            ->groupBy('test_date')
            ->map(fn ($testsOnDate) => $testsOnDate->max('mileage'))
            ->sortKeys()
            ->values();

        for ($i = 1; $i < $mileageByDate->count(); $i++) {
            if ($mileageByDate[$i] < $mileageByDate[$i - 1]) {
                return true;
            }
        }

        return false;
    }
}
