<?php

namespace App\Services\Reports;

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

    private static function mileageWentBackwards(VehicleHistory $history): bool
    {
        $tests = collect($history->mot_history ?? [])
            ->filter(fn ($test) => isset($test['mileage'], $test['test_date']))
            ->sortBy('test_date')
            ->values();

        for ($i = 1; $i < $tests->count(); $i++) {
            if ($tests[$i]['mileage'] < $tests[$i - 1]['mileage']) {
                return true;
            }
        }

        return false;
    }
}
