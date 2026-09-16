<?php

namespace Tests\Feature;

use App\Models\AdminMetricReset;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminMetricResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_non_admin_cannot_reset_the_revenue_counter(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)->post(route('admin.metrics.reset-revenue'))->assertForbidden();
    }

    public function test_an_admin_can_reset_the_revenue_counter(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $before = AdminMetricReset::pointFor('revenue_today');

        $this->travel(5)->minutes();

        $this->actingAs($admin)
            ->post(route('admin.metrics.reset-revenue'))
            ->assertRedirect(route('admin.dashboard'));

        $this->assertTrue(AdminMetricReset::pointFor('revenue_today')->gt($before));
    }
}
