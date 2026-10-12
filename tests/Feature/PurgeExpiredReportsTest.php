<?php

namespace Tests\Feature;

use App\Models\BidRecommendation;
use App\Models\DamageAnalysis;
use App\Models\Payment;
use App\Models\RepairEstimate;
use App\Models\Report;
use App\Models\SalvageAuctionCheck;
use App\Models\SalvageAuctionImage;
use App\Models\User;
use App\Models\VehicleCheck;
use App\Models\VehicleCheckImage;
use App\Models\VehicleHistory;
use App\Models\VehicleValuation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PurgeExpiredReportsTest extends TestCase
{
    use RefreshDatabase;

    private function fullyCompletedCheck(array $overrides = []): VehicleCheck
    {
        $check = VehicleCheck::factory()->create(array_merge([
            'type' => VehicleCheck::TYPE_REBUILD,
            'status' => VehicleCheck::STATUS_COMPLETED,
            'expires_at' => now()->subDay(),
        ], $overrides));

        Storage::fake('local');
        $path = "vehicle-check-uploads/{$check->id}-front.jpg";
        Storage::disk('local')->put($path, 'fake-image-bytes');

        VehicleCheckImage::create(['vehicle_check_id' => $check->id, 'disk' => 'local', 'path' => $path, 'position' => 0, 'source' => 'uploaded']);

        $report = Report::create(['vehicle_check_id' => $check->id, 'type' => VehicleCheck::TYPE_REBUILD, 'headline_summary' => 'Test.']);
        Storage::disk('local')->put('reports/'.$check->id.'/report.pdf', 'fake-pdf-bytes');
        $report->update(['pdf_disk' => 'local', 'pdf_path' => 'reports/'.$check->id.'/report.pdf', 'pdf_generated_at' => now()]);

        $salvageCheck = SalvageAuctionCheck::create([
            'vehicle_check_id' => $check->id,
            'record_found' => true,
            'records' => [['recordId' => 123, 'imageUrls' => ['https://example.com/photo.jpg']]],
        ]);
        $salvageImagePath = "salvage-auction/{$salvageCheck->id}/0-0.jpg";
        Storage::disk('local')->put($salvageImagePath, 'fake-salvage-image-bytes');
        SalvageAuctionImage::create([
            'salvage_auction_check_id' => $salvageCheck->id,
            'record_index' => 0,
            'source_url' => 'https://example.com/photo.jpg',
            'disk' => 'local',
            'path' => $salvageImagePath,
            'position' => 0,
        ]);

        VehicleHistory::create(['vehicle_check_id' => $check->id]);
        VehicleValuation::create(['vehicle_check_id' => $check->id]);
        DamageAnalysis::create(['vehicle_check_id' => $check->id]);
        RepairEstimate::create(['vehicle_check_id' => $check->id, 'low_estimate' => 100, 'expected_estimate' => 150, 'high_estimate' => 200]);
        BidRecommendation::create([
            'vehicle_check_id' => $check->id,
            'expected_resale_value' => 1000, 'total_repair_cost' => 150, 'auction_fees' => 50,
            'transport_cost' => 50, 'service_mot_allowance' => 50, 'contingency' => 50,
            'required_margin' => 100, 'maximum_acquisition_price' => 500,
            'recommended_bid' => 400, 'absolute_maximum' => 450,
        ]);

        return $check->fresh();
    }

    public function test_it_deletes_all_content_and_files_but_keeps_the_check_row_as_a_stub(): void
    {
        $check = $this->fullyCompletedCheck();
        $imagePath = $check->images->first()->path;
        $pdfPath = $check->report->pdf_path;
        $salvageImagePath = $check->salvageAuctionCheck->images->first()->path;

        $this->artisan('reports:purge-expired')->assertSuccessful();

        $check->refresh();

        $this->assertNotNull($check->purged_at);
        $this->assertNotNull($check->id, 'The vehicle_checks row itself must survive as a history stub.');

        $this->assertDatabaseMissing('vehicle_check_images', ['vehicle_check_id' => $check->id]);
        $this->assertDatabaseMissing('reports', ['vehicle_check_id' => $check->id]);
        $this->assertDatabaseMissing('vehicle_histories', ['vehicle_check_id' => $check->id]);
        $this->assertDatabaseMissing('vehicle_valuations', ['vehicle_check_id' => $check->id]);
        $this->assertDatabaseMissing('damage_analyses', ['vehicle_check_id' => $check->id]);
        $this->assertDatabaseMissing('repair_estimates', ['vehicle_check_id' => $check->id]);
        $this->assertDatabaseMissing('bid_recommendations', ['vehicle_check_id' => $check->id]);

        Storage::disk('local')->assertMissing($imagePath);
        Storage::disk('local')->assertMissing($pdfPath);
        Storage::disk('local')->assertMissing($salvageImagePath);
    }

    /**
     * Salvage auction images are tied to this same purge window (see the
     * comment in PurgeExpiredReports::purge()) rather than any separate
     * expiry mechanism — the files and DB rows are deleted explicitly,
     * without touching the parent SalvageAuctionCheck row itself, which is
     * a deliberate, narrower scope than the rest of this method (that row
     * is a pre-existing gap this command never purged, and fixing that is
     * out of scope here).
     */
    public function test_it_deletes_salvage_images_but_leaves_the_salvage_auction_check_row_itself(): void
    {
        $check = $this->fullyCompletedCheck();
        $salvageCheck = $check->salvageAuctionCheck;
        $salvageImagePath = $salvageCheck->images->first()->path;

        $this->artisan('reports:purge-expired')->assertSuccessful();

        $this->assertDatabaseMissing('salvage_auction_images', ['salvage_auction_check_id' => $salvageCheck->id]);
        $this->assertDatabaseHas('salvage_auction_checks', ['id' => $salvageCheck->id]);
        Storage::disk('local')->assertMissing($salvageImagePath);
    }

    public function test_it_leaves_payment_and_credit_records_untouched(): void
    {
        $user = User::factory()->create();
        $payment = Payment::create([
            'user_id' => $user->id, 'type' => VehicleCheck::TYPE_REBUILD, 'description' => 'ValeCheck Rebuild',
            'gross' => 14.99, 'net' => 12.49, 'vat' => 2.50, 'vat_rate' => 0.20, 'currency' => 'GBP',
            'status' => Payment::STATUS_PAID,
        ]);
        $check = $this->fullyCompletedCheck(['user_id' => $user->id, 'payment_id' => $payment->id]);

        $this->artisan('reports:purge-expired');

        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'status' => Payment::STATUS_PAID]);
        $this->assertNotNull($check->fresh()->purged_at);
    }

    public function test_a_check_not_yet_past_its_expiry_is_left_untouched(): void
    {
        $check = $this->fullyCompletedCheck(['expires_at' => now()->addDay()]);

        $this->artisan('reports:purge-expired');

        $this->assertNull($check->fresh()->purged_at);
        $this->assertDatabaseHas('reports', ['vehicle_check_id' => $check->id]);
    }

    public function test_a_non_completed_check_is_left_untouched_even_with_a_past_expiry(): void
    {
        $check = VehicleCheck::factory()->create([
            'status' => VehicleCheck::STATUS_PROCESSING,
            'expires_at' => now()->subDay(),
        ]);

        $this->artisan('reports:purge-expired');

        $this->assertNull($check->fresh()->purged_at);
    }
}
