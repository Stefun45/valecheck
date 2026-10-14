<?php

namespace Tests\Feature;

use App\Models\SalvageAuctionCheck;
use App\Models\VehicleCheck;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Covers database/migrations/2026_10_14_090000_drop_salvage_auction_images_table.php
 * in isolation. RefreshDatabase already runs every migration in order, so by
 * the time a test starts, salvage_auction_images has already been created
 * and dropped again - this test recreates it (exactly as the already-applied
 * 2026_10_12_134227 migration did in production) to simulate production's
 * real starting state: a table that exists, with one stray row pointing at
 * a stored file, before this migration runs.
 */
class DropSalvageAuctionImagesTableMigrationTest extends TestCase
{
    use RefreshDatabase;

    private function recreateTable(): void
    {
        (require base_path('database/migrations/2026_10_12_134227_create_salvage_auction_images_table.php'))->up();
    }

    private function runDropMigration(): void
    {
        (require base_path('database/migrations/2026_10_14_090000_drop_salvage_auction_images_table.php'))->up();
    }

    public function test_it_deletes_every_existing_rows_stored_file_and_drops_the_table(): void
    {
        Storage::fake('local');
        $this->recreateTable();

        $check = VehicleCheck::factory()->create();
        $salvageCheck = SalvageAuctionCheck::create(['vehicle_check_id' => $check->id, 'record_found' => true, 'records' => []]);

        // Realistically the only row this table ever picked up in
        // production - a mis-saved HTML page, not a real photo.
        $firstPath = 'salvage-auction/32/0-0.jpg';
        Storage::disk('local')->put($firstPath, 'not actually an image - a saved HTML page');
        DB::table('salvage_auction_images')->insert([
            'salvage_auction_check_id' => $salvageCheck->id,
            'record_index' => 0,
            'source_url' => 'https://poctra.com/listing/59749706',
            'disk' => 'local',
            'path' => $firstPath,
            'position' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // A second row, on a different disk, to confirm the migration loops
        // over every row rather than assuming there is only ever one.
        $secondPath = 'salvage-auction/32/0-1.jpg';
        Storage::fake('public');
        Storage::disk('public')->put($secondPath, 'also not a real image');
        DB::table('salvage_auction_images')->insert([
            'salvage_auction_check_id' => $salvageCheck->id,
            'record_index' => 0,
            'source_url' => 'https://poctra.com/listing/59749706',
            'disk' => 'public',
            'path' => $secondPath,
            'position' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertTrue(Schema::hasTable('salvage_auction_images'));

        $this->runDropMigration();

        Storage::disk('local')->assertMissing($firstPath);
        Storage::disk('public')->assertMissing($secondPath);
        $this->assertFalse(Schema::hasTable('salvage_auction_images'));

        // The migration only ever touches salvage_auction_images - the
        // parent row and its vehicle check are untouched.
        $this->assertDatabaseHas('salvage_auction_checks', ['id' => $salvageCheck->id]);
    }

    public function test_it_drops_the_table_cleanly_when_no_rows_exist(): void
    {
        $this->recreateTable();

        $this->assertTrue(Schema::hasTable('salvage_auction_images'));

        $this->runDropMigration();

        $this->assertFalse(Schema::hasTable('salvage_auction_images'));
    }

    public function test_a_missing_stored_file_does_not_stop_the_table_being_dropped(): void
    {
        Storage::fake('local');
        $this->recreateTable();

        $check = VehicleCheck::factory()->create();
        $salvageCheck = SalvageAuctionCheck::create(['vehicle_check_id' => $check->id, 'record_found' => true, 'records' => []]);

        // No file actually stored at this path - the row references a file
        // that's already gone.
        DB::table('salvage_auction_images')->insert([
            'salvage_auction_check_id' => $salvageCheck->id,
            'record_index' => 0,
            'source_url' => 'https://poctra.com/listing/59749706',
            'disk' => 'local',
            'path' => 'salvage-auction/999/0-0.jpg',
            'position' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->runDropMigration();

        $this->assertFalse(Schema::hasTable('salvage_auction_images'));
    }
}
