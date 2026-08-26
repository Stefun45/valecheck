<?php

namespace App\Jobs;

use App\Models\AiUsage;
use App\Models\DamageAnalysis;
use App\Models\VehicleCheck;
use App\Services\Ai\AiProvider;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Throwable;

class AnalyseImages implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public array $backoff = [15, 45];

    public function __construct(public int $vehicleCheckId) {}

    public function handle(AiProvider $provider): void
    {
        $check = VehicleCheck::findOrFail($this->vehicleCheckId);
        $check->update(['stage' => 'analysing_images']);

        $images = $check->images;

        if ($images->isEmpty()) {
            DamageAnalysis::updateOrCreate(
                ['vehicle_check_id' => $check->id],
                [
                    'summary' => 'No photographs were supplied, so no damage assessment could be performed.',
                    'confidence' => 'low',
                    'images_analysed' => 0,
                ]
            );

            return;
        }

        $paths = $images->map(fn ($image) => Storage::disk($image->disk)->path($image->path))->all();
        $vehicleData = $check->toVehicleData();

        $startedAt = microtime(true);

        try {
            $result = $provider->analyseVehicleImages($paths, $vehicleData);
        } catch (Throwable $e) {
            AiUsage::create([
                'vehicle_check_id' => $check->id,
                'provider' => config('valecheck.ai.provider'),
                'model' => config('valecheck.ai.model'),
                'stage' => 'image_analysis',
                'image_count' => count($paths),
                'duration_ms' => (int) ((microtime(true) - $startedAt) * 1000),
                'success' => false,
                'error_message' => $e->getMessage(),
            ]);

            throw $e;
        }

        AiUsage::create([
            'vehicle_check_id' => $check->id,
            'provider' => config('valecheck.ai.provider'),
            'model' => config('valecheck.ai.model'),
            'stage' => 'image_analysis',
            'input_tokens' => $result->inputTokens,
            'output_tokens' => $result->outputTokens,
            'image_count' => $result->imagesAnalysed,
            'estimated_cost' => $result->estimatedCost,
            'duration_ms' => (int) ((microtime(true) - $startedAt) * 1000),
            'success' => true,
        ]);

        $analysis = DamageAnalysis::updateOrCreate(
            ['vehicle_check_id' => $check->id],
            [
                'summary' => $result->summary,
                'confidence' => $result->confidence,
                'images_analysed' => $result->imagesAnalysed,
            ]
        );

        $analysis->findings()->delete();

        foreach ($result->findings as $finding) {
            $analysis->findings()->create([
                'component' => $finding->component,
                'condition' => $finding->condition,
                'severity' => $finding->severity,
                'recommended_action' => $finding->recommendedAction,
                'confidence' => $finding->confidence,
                'explanation' => $finding->explanation,
            ]);
        }
    }
}
