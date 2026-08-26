<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmailVerificationNotRequiredTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_unverified_user_can_access_the_dashboard(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)->get(route('dashboard'))->assertOk();
    }

    public function test_an_unverified_user_can_access_their_reports_page(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)->get(route('reports.index'))->assertOk();
    }

    public function test_an_unverified_admin_can_access_the_admin_dashboard(): void
    {
        $admin = User::factory()->unverified()->create(['is_admin' => true]);

        $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk();
    }
}
