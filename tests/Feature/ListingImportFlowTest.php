<?php

namespace Tests\Feature;

use App\Jobs\ImportListing;
use App\Livewire\VehicleCheck\StartCheck;
use App\Models\ListingImage;
use App\Models\ListingImport;
use App\Models\User;
use App\Models\VehicleCheck;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ListingImportFlowTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Deliberately un-verified — no free Rebuild credits are granted, so
     * submit() always resolves to "purchase" funding and never dispatches
     * the processing pipeline. These tests are only concerned with the
     * listing-import wiring, not full report generation.
     */
    private function user(): User
    {
        return User::factory()->create();
    }

    public function test_importing_a_listing_dispatches_the_job_and_creates_a_pending_row(): void
    {
        config(['valecheck.listing_import.enabled' => true]);
        Bus::fake();

        $this->actingAs($this->user());

        Livewire::test(StartCheck::class)
            ->set('registration', 'AB12CDE')
            ->call('lookupVehicle')
            ->call('confirmVehicle', true)
            ->call('choose', 'rebuild')
            ->set('listing_url', 'https://example.com/listing/1')
            ->call('importListing')
            ->assertSet('importStatus', 'importing');

        $this->assertDatabaseHas('listing_imports', ['url' => 'https://example.com/listing/1']);
        Bus::assertDispatched(ImportListing::class);
    }

    public function test_import_is_declined_gracefully_when_the_feature_is_disabled(): void
    {
        config(['valecheck.listing_import.enabled' => false]);
        Bus::fake();

        $this->actingAs($this->user());

        Livewire::test(StartCheck::class)
            ->set('registration', 'AB12CDE')
            ->call('lookupVehicle')
            ->call('confirmVehicle', true)
            ->call('choose', 'rebuild')
            ->set('listing_url', 'https://example.com/listing/1')
            ->call('importListing')
            ->assertSet('importStatus', 'unavailable');

        Bus::assertNotDispatched(ImportListing::class);
    }

    public function test_a_cached_import_is_reused_without_dispatching_a_new_job(): void
    {
        config(['valecheck.listing_import.enabled' => true]);

        $this->actingAs($this->user());

        $existing = ListingImport::create([
            'url' => 'https://example.com/listing/1',
            'url_hash' => sha1('https://example.com/listing/1'),
            'domain' => 'example.com',
            'provider' => 'generic',
            'status' => ListingImport::STATUS_SUCCESS,
            'data' => ['make' => ['value' => 'BMW', 'found' => true]],
            'image_count_found' => 0,
        ]);

        Bus::fake();

        Livewire::test(StartCheck::class)
            ->set('registration', 'AB12CDE')
            ->call('lookupVehicle')
            ->call('confirmVehicle', true)
            ->call('choose', 'rebuild')
            ->set('listing_url', 'https://example.com/listing/1')
            ->call('importListing')
            ->assertSet('importStatus', 'found')
            ->assertSet('listingImportId', $existing->id);

        Bus::assertNotDispatched(ImportListing::class);
        $this->assertSame(1, ListingImport::count());
    }

    public function test_manual_fields_remain_fully_usable_when_import_fails(): void
    {
        config(['valecheck.listing_import.enabled' => true]);

        $this->actingAs($this->user());

        ListingImport::create([
            'url' => 'https://blocked.example/listing/1',
            'url_hash' => sha1('https://blocked.example/listing/1'),
            'domain' => 'blocked.example',
            'provider' => 'generic',
            'status' => ListingImport::STATUS_FAILED,
            'error_message' => 'No structured data found.',
        ]);

        Livewire::test(StartCheck::class)
            ->set('registration', 'AB12CDE')
            ->call('lookupVehicle')
            ->call('confirmVehicle', true)
            ->call('choose', 'rebuild')
            ->set('listing_url', 'https://blocked.example/listing/1')
            ->call('importListing')
            ->assertSet('importStatus', 'failed')
            ->set('mileage', 45000)
            ->set('current_bid', 2500)
            ->call('submit');

        $check = VehicleCheck::where('registration', 'AB12CDE')->firstOrFail();
        $this->assertSame(45000, $check->mileage);
        $this->assertSame('purchase', $check->funding_source);
    }

    public function test_using_imported_data_prefills_fields_and_links_imported_images(): void
    {
        config(['valecheck.listing_import.enabled' => true]);
        Storage::fake('local');
        $this->actingAs($this->user());

        $listingImport = ListingImport::create([
            'url' => 'https://example.com/listing/1',
            'url_hash' => sha1('https://example.com/listing/1'),
            'domain' => 'example.com',
            'provider' => 'generic',
            'status' => ListingImport::STATUS_SUCCESS,
            'data' => [
                'mileage' => ['value' => 82000, 'found' => true],
                'asking_price' => ['value' => 7500, 'found' => true],
                'description' => ['value' => 'Damaged but runs and drives.', 'found' => true],
            ],
            'image_count_found' => 1,
        ]);

        $image = ListingImage::create([
            'listing_import_id' => $listingImport->id,
            'source_url' => 'https://example.com/img1.jpg',
            'disk' => 'local',
            'path' => 'listing-import/1/abc.jpg',
            'hash' => 'abc',
            'position' => 0,
            'status' => ListingImage::STATUS_DOWNLOADED,
        ]);

        Storage::disk('local')->put($image->path, 'fake-bytes');

        Livewire::test(StartCheck::class)
            ->set('registration', 'AB12CDE')
            ->call('lookupVehicle')
            ->call('confirmVehicle', true)
            ->call('choose', 'rebuild')
            ->set('listing_url', $listingImport->url)
            ->call('importListing')
            ->assertSet('importStatus', 'found')
            ->call('useImportedData')
            ->assertSet('mileage', 82000)
            ->assertSet('asking_price', 7500.0)
            ->assertSet('importedImageIds', [$image->id])
            ->call('submit');

        $check = VehicleCheck::where('registration', 'AB12CDE')->firstOrFail();
        $this->assertSame(82000, $check->mileage);
        $this->assertSame($listingImport->id, $check->listing_import_id);
        $this->assertSame('imported', $check->listing_data_sources['mileage']);
        $this->assertSame(1, $check->images()->where('source', 'imported')->count());
    }
}
