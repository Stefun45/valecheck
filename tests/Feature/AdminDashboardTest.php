<?php

namespace Tests\Feature;

use App\Models\ListingImport;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_admin_cannot_access_admin_dashboard(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)
            ->get(route('admin.dashboard'))
            ->assertForbidden();
    }

    public function test_admin_can_view_dashboard_with_revenue_metrics(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        Payment::create([
            'user_id' => $admin->id,
            'type' => 'rebuild',
            'description' => 'ValeCheck Rebuild',
            'gross' => 14.99,
            'net' => 12.49,
            'vat' => 2.50,
            'vat_rate' => 0.20,
            'currency' => 'GBP',
            'status' => Payment::STATUS_PAID,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSeeText('£14.99');
    }

    public function test_admin_dashboard_shows_listing_import_stats_by_provider(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        ListingImport::create([
            'url' => 'https://example.com/listing/1',
            'url_hash' => sha1('https://example.com/listing/1'),
            'domain' => 'example.com',
            'provider' => 'generic',
            'status' => ListingImport::STATUS_SUCCESS,
            'duration_ms' => 500,
            'image_count_found' => 4,
        ]);

        ListingImport::create([
            'url' => 'https://www.ebay.co.uk/itm/1',
            'url_hash' => sha1('https://www.ebay.co.uk/itm/1'),
            'domain' => 'www.ebay.co.uk',
            'provider' => 'ebay',
            'status' => ListingImport::STATUS_BLOCKED,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSeeText('Listing Import')
            ->assertSeeText('generic')
            ->assertSeeText('ebay');
    }
}
