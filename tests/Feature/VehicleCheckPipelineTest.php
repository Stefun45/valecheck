<?php

namespace Tests\Feature;

use App\Jobs\AnalyseImages;
use App\Jobs\CalculateDealScore;
use App\Jobs\CalculateMaximumBid;
use App\Jobs\CalculateRepair;
use App\Jobs\GenerateReport;
use App\Jobs\RetrieveSalvageAuctionHistory;
use App\Jobs\RetrieveValuation;
use App\Jobs\RetrieveVehicleHistory;
use App\Jobs\RetrieveVehicleImage;
use App\Jobs\RetrieveVehicleTaxCost;
use App\Models\VehicleCheck;
use App\Services\Pipeline\VehicleCheckPipeline;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

/**
 * Vehicle imagery is a paid One Auto call, gated to Plus and Rebuild per
 * the confirmed tier decision — Check must never dispatch it.
 */
class VehicleCheckPipelineTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_check_report_does_not_dispatch_a_vehicle_image_lookup(): void
    {
        Bus::fake();

        $check = VehicleCheck::factory()->create(['type' => VehicleCheck::TYPE_CHECK]);

        app(VehicleCheckPipeline::class)->dispatch($check);

        // assertChained requires an exact-length match against the real
        // chain — asserting this shorter chain succeeds only if
        // RetrieveVehicleImage is genuinely absent from it.
        Bus::assertChained([
            RetrieveVehicleHistory::class,
            GenerateReport::class,
        ]);
    }

    public function test_a_plus_report_dispatches_a_vehicle_image_lookup(): void
    {
        Bus::fake();

        $check = VehicleCheck::factory()->create(['type' => VehicleCheck::TYPE_PLUS]);

        app(VehicleCheckPipeline::class)->dispatch($check);

        Bus::assertChained([
            RetrieveVehicleHistory::class,
            RetrieveVehicleImage::class,
            RetrieveValuation::class,
            RetrieveSalvageAuctionHistory::class,
            RetrieveVehicleTaxCost::class,
            GenerateReport::class,
        ]);
    }

    public function test_a_rebuild_report_dispatches_a_vehicle_image_lookup(): void
    {
        Bus::fake();

        $check = VehicleCheck::factory()->create(['type' => VehicleCheck::TYPE_REBUILD]);

        app(VehicleCheckPipeline::class)->dispatch($check);

        Bus::assertChained([
            RetrieveVehicleHistory::class,
            RetrieveVehicleImage::class,
            RetrieveValuation::class,
            AnalyseImages::class,
            CalculateRepair::class,
            CalculateMaximumBid::class,
            CalculateDealScore::class,
            GenerateReport::class,
        ]);
    }

    public function test_a_check_to_plus_upgrade_dispatches_a_vehicle_image_lookup(): void
    {
        Bus::fake();

        $check = VehicleCheck::factory()->create(['type' => VehicleCheck::TYPE_PLUS]);

        app(VehicleCheckPipeline::class)->dispatchUpgrade($check);

        Bus::assertChained([
            RetrieveVehicleImage::class,
            RetrieveValuation::class,
            RetrieveSalvageAuctionHistory::class,
            RetrieveVehicleTaxCost::class,
            GenerateReport::class,
        ]);
    }
}
