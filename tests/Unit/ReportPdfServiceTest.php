<?php

namespace Tests\Unit;

use App\Models\Report;
use App\Models\VehicleCheck;
use App\Services\Reports\ReportPdfService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ReportPdfServiceTest extends TestCase
{
    use RefreshDatabase;

    private function completedCheckWithReport(): VehicleCheck
    {
        $check = VehicleCheck::factory()->create([
            'type' => VehicleCheck::TYPE_CHECK,
            'status' => VehicleCheck::STATUS_COMPLETED,
        ]);

        Report::create([
            'vehicle_check_id' => $check->id,
            'type' => VehicleCheck::TYPE_CHECK,
            'headline_summary' => 'No write-off or finance markers found.',
        ]);

        return $check->fresh();
    }

    public function test_it_generates_and_stores_a_pdf(): void
    {
        Storage::fake('local');

        $check = $this->completedCheckWithReport();

        $report = app(ReportPdfService::class)->generate($check);

        $this->assertSame('local', $report->pdf_disk);
        $this->assertNotNull($report->pdf_path);
        $this->assertNotNull($report->pdf_generated_at);
        Storage::disk('local')->assertExists($report->pdf_path);
    }

    public function test_it_does_not_regenerate_an_already_generated_pdf(): void
    {
        Storage::fake('local');

        $check = $this->completedCheckWithReport();
        $service = app(ReportPdfService::class);

        $first = $service->generate($check);
        $second = $service->generate($check->fresh());

        $this->assertSame($first->pdf_path, $second->pdf_path);
        $this->assertTrue($first->pdf_generated_at->equalTo($second->pdf_generated_at));
    }
}
