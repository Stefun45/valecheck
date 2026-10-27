<?php

namespace Tests\Feature;

use App\Models\AiUsage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Covers the mobile-web "scan number plate" camera upload endpoint
 * (PlateScanController) — mirrors the Http::fake('api.anthropic.com/*', ...)
 * pattern already used for Rebuild's damage analysis in
 * VehicleCheckFlowTest, since this hits the same raw Anthropic Messages API
 * via AnthropicProvider's shared HTTP-calling trait.
 */
class PlateScanTest extends TestCase
{
    use RefreshDatabase;

    private function fakePlateResponse(?string $plate, array $usage = ['input_tokens' => 300, 'output_tokens' => 10]): void
    {
        Http::fake([
            'api.anthropic.com/*' => Http::sequence()->push([
                'content' => [[
                    'type' => 'tool_use',
                    'name' => 'report_number_plate',
                    'input' => ['plate' => $plate],
                ]],
                'usage' => $usage,
            ]),
        ]);
    }

    private function scan(?UploadedFile $photo = null)
    {
        return $this->post(route('plate-scan'), [
            'photo' => $photo ?? UploadedFile::fake()->image('plate.jpg'),
        ], ['Accept' => 'application/json']);
    }

    public function test_a_clear_photo_is_recognised_and_normalised(): void
    {
        $this->fakePlateResponse('AB12 CDE');

        $this->scan()
            ->assertOk()
            ->assertJson(['success' => true, 'registration' => 'AB12CDE']);

        $usage = AiUsage::sole();
        $this->assertSame('anthropic', $usage->provider);
        $this->assertSame(config('valecheck.ai.plate_scan_model'), $usage->model);
        $this->assertSame('plate_scan', $usage->stage);
        $this->assertTrue($usage->success);
        $this->assertSame(300, $usage->input_tokens);
        $this->assertSame(10, $usage->output_tokens);
        $this->assertNotNull($usage->estimated_cost);
    }

    public function test_claude_finding_no_plate_degrades_gracefully_without_filling_the_form(): void
    {
        $this->fakePlateResponse(null);

        $this->scan()
            ->assertOk()
            ->assertJson(['success' => false, 'reason' => 'not_read']);

        $usage = AiUsage::sole();
        $this->assertTrue($usage->success, 'The API call itself succeeded — it just found no plate.');
    }

    public function test_a_result_failing_uk_plate_format_validation_is_never_accepted(): void
    {
        // Claude's output is never trusted blindly — only something that
        // actually matches the real UK plate format is ever returned.
        $this->fakePlateResponse('NOT A REAL PLATE');

        $this->scan()
            ->assertOk()
            ->assertJson(['success' => false, 'reason' => 'invalid_format']);

        $usage = AiUsage::sole();
        $this->assertTrue($usage->success, 'The API call itself succeeded — the model just returned an unusable value.');
    }

    public function test_an_anthropic_api_failure_degrades_gracefully_instead_of_a_server_error(): void
    {
        Http::fake([
            'api.anthropic.com/*' => Http::response(['error' => 'server error'], 500),
        ]);

        $this->scan()
            ->assertOk()
            ->assertJson(['success' => false, 'reason' => 'not_read']);

        $usage = AiUsage::sole();
        $this->assertFalse($usage->success);
        $this->assertNotNull($usage->error_message);
    }

    public function test_rate_limiting_kicks_in_after_the_configured_threshold(): void
    {
        // One single Http::fake() call queuing 20 responses — calling
        // Http::fake() again inside the loop would register an *additional*
        // stub each time rather than replacing it, so an earlier, already
        // exhausted sequence could end up matched instead of a fresh one.
        $sequence = Http::sequence();

        for ($i = 0; $i < 20; $i++) {
            $sequence->push([
                'content' => [[
                    'type' => 'tool_use',
                    'name' => 'report_number_plate',
                    'input' => ['plate' => 'AB12CDE'],
                ]],
                'usage' => ['input_tokens' => 300, 'output_tokens' => 10],
            ]);
        }

        Http::fake(['api.anthropic.com/*' => $sequence]);

        for ($i = 0; $i < 20; $i++) {
            $this->scan()->assertOk()->assertJson(['success' => true]);
        }

        // The 21st attempt is blocked by the rate limiter before it ever
        // reaches Claude — no 21st response was even queued above.
        $this->scan()
            ->assertStatus(429)
            ->assertJson(['success' => false, 'reason' => 'rate_limited']);
    }

    public function test_a_non_image_upload_is_rejected_by_validation(): void
    {
        $this->post(route('plate-scan'), [
            'photo' => UploadedFile::fake()->create('plate.pdf', 100, 'application/pdf'),
        ], ['Accept' => 'application/json'])->assertStatus(422);
    }
}
