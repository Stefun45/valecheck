<?php

namespace Tests\Unit;

use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleCheck;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A sequential id in a customer-facing URL (checks/9) reveals roughly how
 * many checks have ever been run — public_id is a short random identifier
 * used for routing instead, so the real primary key never appears in a URL.
 */
class VehicleCheckPublicIdTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_public_id_is_generated_automatically_on_creation(): void
    {
        $check = VehicleCheck::factory()->create();

        $this->assertNotNull($check->public_id);
        $this->assertSame(11, strlen($check->public_id));
    }

    public function test_public_ids_are_unique_across_checks(): void
    {
        $checks = VehicleCheck::factory()->count(20)->create();

        $this->assertCount(20, $checks->pluck('public_id')->unique());
    }

    public function test_the_route_key_is_public_id_not_the_sequential_id(): void
    {
        $check = VehicleCheck::factory()->create();

        $this->assertSame('public_id', $check->getRouteKeyName());
        $this->assertSame($check->public_id, $check->getRouteKey());
    }

    public function test_generated_urls_use_the_public_id_not_the_sequential_id(): void
    {
        $check = VehicleCheck::factory()->create();

        $url = route('vehicle-checks.show', $check);

        $this->assertStringContainsString($check->public_id, $url);
        $this->assertStringNotContainsString("/checks/{$check->id}", $url);
    }

    public function test_public_id_is_not_mass_assignable(): void
    {
        // Factories deliberately bypass fillable guarding (they use
        // forceFill), so this exercises the real Model::create() path
        // instead — the one actual application code goes through.
        $user = User::factory()->create();
        $vehicle = Vehicle::factory()->create();

        $check = VehicleCheck::create([
            'user_id' => $user->id,
            'vehicle_id' => $vehicle->id,
            'type' => VehicleCheck::TYPE_CHECK,
            'status' => VehicleCheck::STATUS_PENDING,
            'funding_source' => 'purchase',
            'registration' => 'AB12CDE',
            'public_id' => 'not-fillable',
        ]);

        $this->assertNotSame('not-fillable', $check->public_id);
        $this->assertSame(11, strlen($check->public_id));
    }
}
