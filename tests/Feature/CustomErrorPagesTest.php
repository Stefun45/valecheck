<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\VehicleCheck;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomErrorPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Laravel only renders custom errors/{code}.blade.php views when
        // debug is off — with debug on it shows the developer trace page
        // regardless of what's in resources/views/errors. Forcing this off
        // is what makes this test representative of production.
        config(['app.debug' => false]);
    }

    public function test_a_missing_page_shows_the_branded_404_not_a_generic_one(): void
    {
        $this->get('/this-route-does-not-exist')
            ->assertNotFound()
            ->assertSeeText('Page not found')
            ->assertSeeText('ValeCheck');
    }

    public function test_viewing_another_users_report_shows_the_branded_403(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $check = VehicleCheck::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($intruder)
            ->get(route('vehicle-checks.show', $check))
            ->assertForbidden()
            ->assertSeeText('Access denied')
            ->assertSeeText('ValeCheck');
    }

    public function test_every_custom_error_view_renders_without_error(): void
    {
        foreach (['404', '403', '419', '500'] as $code) {
            $html = view("errors.{$code}")->render();

            $this->assertStringContainsString('ValeCheck', $html);
        }
    }
}
