<?php

namespace App\Services\Bidding;

use App\DataTransferObjects\BidRecommendationResult;
use App\DataTransferObjects\DamageFindingData;
use App\DataTransferObjects\DealScoreResult;

/**
 * Deterministic 0-100 deal score. Every sub-score is plain arithmetic over
 * numbers already produced elsewhere (bid math, damage findings, provider
 * confidence levels) — nothing here is judged by AI.
 */
class DealScoreService
{
    private const CONFIDENCE_SCORES = [
        'high' => 1.0,
        'medium' => 0.6,
        'low' => 0.3,
    ];

    private const SEVERITY_POINTS = [
        'high' => 3,
        'medium' => 2,
        'low' => 1,
    ];

    /**
     * @param  array<int, DamageFindingData>  $damageFindings
     */
    public function score(
        float $currentPrice,
        BidRecommendationResult $bid,
        array $damageFindings,
        string $marketConfidence,
        string $aiConfidence,
        int $imagesAnalysed,
    ): DealScoreResult {
        $weights = config('valecheck.deal_score.weights');
        $thresholds = config('valecheck.deal_score.thresholds');

        $marginScore = $this->marginScore($currentPrice, $bid);
        $damageScore = $this->damageSeverityScore($damageFindings);
        $marketScore = self::CONFIDENCE_SCORES[$marketConfidence] ?? 0.5;
        $completenessScore = $this->dataCompletenessScore($imagesAnalysed);
        $aiScore = self::CONFIDENCE_SCORES[$aiConfidence] ?? 0.5;

        $breakdown = [
            'margin' => round($marginScore * $weights['margin']),
            'damage_severity' => round($damageScore * $weights['damage_severity']),
            'market_confidence' => round($marketScore * $weights['market_confidence']),
            'data_completeness' => round($completenessScore * $weights['data_completeness']),
            'ai_confidence' => round($aiScore * $weights['ai_confidence']),
        ];

        $score = (int) min(100, array_sum($breakdown));

        $recommendation = match (true) {
            $score >= $thresholds['buy'] => 'buy',
            $score >= $thresholds['maybe'] => 'maybe',
            default => 'walk_away',
        };

        $explanation = sprintf(
            'Margin %d/%d, damage severity %d/%d, market confidence %d/%d, data completeness %d/%d, AI confidence %d/%d.',
            $breakdown['margin'], $weights['margin'],
            $breakdown['damage_severity'], $weights['damage_severity'],
            $breakdown['market_confidence'], $weights['market_confidence'],
            $breakdown['data_completeness'], $weights['data_completeness'],
            $breakdown['ai_confidence'], $weights['ai_confidence'],
        );

        return new DealScoreResult(
            score: $score,
            recommendation: $recommendation,
            explanation: $explanation,
            breakdown: $breakdown,
        );
    }

    private function marginScore(float $currentPrice, BidRecommendationResult $bid): float
    {
        if ($bid->expectedResaleValue <= 0) {
            return 0.0;
        }

        $headroom = $bid->maximumAcquisitionPrice - $currentPrice;
        $headroomRatio = $headroom / $bid->expectedResaleValue;

        return max(0.0, min(1.0, $headroomRatio / 0.20));
    }

    /**
     * @param  array<int, DamageFindingData>  $damageFindings
     */
    private function damageSeverityScore(array $damageFindings): float
    {
        $points = 0;

        foreach ($damageFindings as $finding) {
            if (! $finding->isDamaged()) {
                continue;
            }

            $points += self::SEVERITY_POINTS[$finding->severity ?? 'medium'] ?? 2;
        }

        return max(0.0, min(1.0, 1 - ($points / 10)));
    }

    private function dataCompletenessScore(int $imagesAnalysed): float
    {
        return match (true) {
            $imagesAnalysed >= 8 => 1.0,
            $imagesAnalysed >= 4 => 0.7,
            $imagesAnalysed >= 1 => 0.4,
            default => 0.1,
        };
    }
}
