<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\VehicleCheck;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_shows_only_the_authenticated_users_checks(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        VehicleCheck::factory()->create(['user_id' => $user->id, 'registration' => 'MINEAB1']);
        VehicleCheck::factory()->create(['user_id' => $other->id, 'registration' => 'NOTMINE']);

        $this->actingAs($user)
            ->get(route('reports.index'))
            ->assertOk()
            ->assertSeeText('MINEAB1')
            ->assertDontSeeText('NOTMINE');
    }

    public function test_a_purged_check_shows_as_expired_with_no_links(): void
    {
        $user = User::factory()->create();
        VehicleCheck::factory()->create([
            'user_id' => $user->id,
            'registration' => 'EXPIRED1',
            'status' => VehicleCheck::STATUS_COMPLETED,
            'purged_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('reports.index'))
            ->assertOk()
            ->assertSeeText('EXPIRED1')
            ->assertSeeText('Expired')
            ->assertSeeText('Data no longer available');
    }

    public function test_a_guest_is_redirected_to_login(): void
    {
        $this->get(route('reports.index'))->assertRedirect(route('login'));
    }

    public function test_the_download_pdf_link_opens_in_a_new_tab(): void
    {
        $user = User::factory()->create();
        $check = VehicleCheck::factory()->create([
            'user_id' => $user->id,
            'status' => VehicleCheck::STATUS_COMPLETED,
        ]);

        $this->actingAs($user)
            ->get(route('reports.index'))
            ->assertOk()
            ->assertSeeHtml('href="'.route('vehicle-checks.pdf', $check).'" target="_blank"');
    }
}
