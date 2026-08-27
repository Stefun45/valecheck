<?php

namespace Tests\Feature;

use App\Models\Report;
use App\Models\User;
use App\Models\VehicleCheck;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ReportPdfControllerTest extends TestCase
{
    use RefreshDatabase;

    private function completedCheckFor(User $user): VehicleCheck
    {
        $check = VehicleCheck::factory()->create([
            'user_id' => $user->id,
            'type' => VehicleCheck::TYPE_CHECK,
            'status' => VehicleCheck::STATUS_COMPLETED,
        ]);

        Report::create(['vehicle_check_id' => $check->id, 'type' => VehicleCheck::TYPE_CHECK, 'headline_summary' => 'Test.']);

        return $check->fresh();
    }

    public function test_the_owner_can_download_their_report_pdf(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $check = $this->completedCheckFor($user);

        $response = $this->actingAs($user)->get(route('vehicle-checks.pdf', $check));

        $response->assertRedirect();
        $this->assertNotNull($check->fresh()->report->pdf_path);
    }

    public function test_another_user_cannot_download_someone_elses_report(): void
    {
        Storage::fake('local');

        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $check = $this->completedCheckFor($owner);

        $this->actingAs($intruder)
            ->get(route('vehicle-checks.pdf', $check))
            ->assertForbidden();
    }

    public function test_a_storage_failure_shows_a_friendly_message_instead_of_a_500(): void
    {
        // Simulates the real incident this covers: a misconfigured PDF disk
        // (e.g. a broken S3/Backblaze endpoint) must never surface as a raw
        // 500 — the customer should land back on their report with an
        // explanation, not a crash page.
        config(['valecheck.reports.pdf_disk' => 'not-a-real-disk']);

        $user = User::factory()->create();
        $check = $this->completedCheckFor($user);

        $this->actingAs($user)
            ->get(route('vehicle-checks.pdf', $check))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertNull($check->fresh()->report->pdf_path);
    }

    public function test_a_purged_reports_pdf_is_no_longer_downloadable(): void
    {
        $user = User::factory()->create();
        $check = VehicleCheck::factory()->create([
            'user_id' => $user->id,
            'type' => VehicleCheck::TYPE_CHECK,
            'status' => VehicleCheck::STATUS_COMPLETED,
            'purged_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('vehicle-checks.pdf', $check))
            ->assertRedirect()
            ->assertSessionHas('error');
    }
}
