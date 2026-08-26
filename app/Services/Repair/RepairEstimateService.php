<?php

namespace App\Services\Repair;

use App\DataTransferObjects\DamageFindingData;
use App\DataTransferObjects\RepairEstimateResult;
use App\DataTransferObjects\RepairItemResult;

/**
 * Turns AI-identified damage findings into a repair cost estimate.
 * The AI only identifies WHAT is likely damaged; this service — plain
 * deterministic application code — calculates the cost, from configurable
 * assumptions in config('valecheck.repair_assumptions').
 */
class RepairEstimateService
{
    private const PAINTABLE_ACTIONS = ['repair', 'replace'];

    /**
     * @param  array<int, DamageFindingData>  $findings
     */
    public function estimate(array $findings): RepairEstimateResult
    {
        $assumptions = config('valecheck.repair_assumptions');

        $items = [];

        foreach ($findings as $finding) {
            if (! $finding->isDamaged()) {
                continue;
            }

            $items[] = $this->estimateItem($finding, $assumptions);
        }

        $subtotal = array_sum(array_map(fn (RepairItemResult $item) => $item->totalCost, $items));

        $materials = round($subtotal * $assumptions['materials_rate'], 2);
        $misc = round($subtotal * $assumptions['misc_rate'], 2);
        $contingency = round($subtotal * $assumptions['contingency_rate'], 2);

        $low = round($subtotal + $materials + $misc, 2);
        $expected = round($low + $contingency, 2);
        $high = round($expected * 1.15, 2);

        return new RepairEstimateResult(
            lowEstimate: $low,
            expectedEstimate: $expected,
            highEstimate: $high,
            items: $items,
            assumptionsSnapshot: $assumptions,
        );
    }

    private function estimateItem(DamageFindingData $finding, array $assumptions): RepairItemResult
    {
        $action = $finding->recommendedAction ?? 'repair';
        $severity = $finding->severity ?? 'medium';

        $basePartCost = $assumptions['part_cost_by_component'][$finding->component]
            ?? $assumptions['part_cost_by_component']['default'];

        $partsCost = match ($action) {
            'replace' => $basePartCost,
            'repair' => $basePartCost * 0.3,
            default => 0.0,
        };

        $hours = $assumptions['hours_by_severity'][$severity] ?? $assumptions['hours_by_severity']['medium'];
        $labourCost = round($hours * $assumptions['labour_rate_per_hour'], 2);

        $paintCost = in_array($action, self::PAINTABLE_ACTIONS, true)
            ? $assumptions['paint_rate_per_panel']
            : 0.0;

        $partsCost = round($partsCost, 2);
        $totalCost = round($partsCost + $labourCost + $paintCost, 2);

        return new RepairItemResult(
            component: $finding->component,
            action: $action,
            partsCost: $partsCost,
            labourHours: $hours,
            labourCost: $labourCost,
            paintCost: $paintCost,
            totalCost: $totalCost,
        );
    }
}
