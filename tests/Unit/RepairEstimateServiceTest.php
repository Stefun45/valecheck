<?php

namespace Tests\Unit;

use App\DataTransferObjects\DamageFindingData;
use App\Services\Repair\RepairEstimateService;
use Tests\TestCase;

class RepairEstimateServiceTest extends TestCase
{
    public function test_estimate_matches_hand_calculated_totals(): void
    {
        $findings = [
            DamageFindingData::fromArray([
                'component' => 'front_left_wing', 'condition' => 'damaged', 'severity' => 'high',
                'recommended_action' => 'replace', 'confidence' => 0.9, 'explanation' => 'Deformation visible.',
            ]),
            DamageFindingData::fromArray([
                'component' => 'front_bumper', 'condition' => 'damaged', 'severity' => 'medium',
                'recommended_action' => 'repair', 'confidence' => 0.8, 'explanation' => 'Scuffing visible.',
            ]),
        ];

        $result = (new RepairEstimateService)->estimate($findings);

        // parts 250 + labour (8h * £55 = 440) + paint 180 = 870
        // parts (250*0.3=75) + labour (4h * £55 = 220) + paint 180 = 475
        // subtotal = 1345; materials 12% = 161.40; misc 5% = 67.25; contingency 10% = 134.50
        $this->assertSame(1573.65, $result->lowEstimate);
        $this->assertSame(1708.15, $result->expectedEstimate);
        $this->assertSame(1964.37, $result->highEstimate);
        $this->assertCount(2, $result->items);
    }

    public function test_undamaged_findings_are_excluded_from_the_estimate(): void
    {
        $findings = [
            DamageFindingData::fromArray(['component' => 'roof', 'condition' => 'ok', 'confidence' => 0.95, 'explanation' => 'No visible damage.']),
        ];

        $result = (new RepairEstimateService)->estimate($findings);

        $this->assertSame(0.0, $result->lowEstimate);
        $this->assertSame(0.0, $result->expectedEstimate);
        $this->assertCount(0, $result->items);
    }
}
