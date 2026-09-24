<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleCheck;
use App\Services\Credits\CreditLedgerService;
use App\Services\Ordering\VehicleCheckOrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VehicleCheckReuseTest extends TestCase
{
    use RefreshDatabase;

    private function submit(User $user, string $registration): VehicleCheck
    {
        return app(VehicleCheckOrderService::class)->submit($user, VehicleCheck::TYPE_PLUS, [
            'registration' => $registration,
        ]);
    }

    public function test_a_completed_plus_check_within_the_reuse_window_is_returned_instead_of_a_new_one(): void
    {
        $user = User::factory()->create();
        app(CreditLedgerService::class)->grantFreeCredits($user, VehicleCheck::TYPE_PLUS, 5);
        $vehicle = Vehicle::factory()->create(['registration' => 'AB12CDE']);
        $existing = VehicleCheck::factory()->create([
            'user_id' => $user->id,
            'vehicle_id' => $vehicle->id,
            'type' => VehicleCheck::TYPE_PLUS,
            'status' => VehicleCheck::STATUS_COMPLETED,
            'registration' => 'AB12CDE',
            'created_at' => now()->subDays(10),
        ]);

        $result = $this->submit($user, 'AB12CDE');

        $this->assertSame($existing->id, $result->id);
        $this->assertSame(1, VehicleCheck::where('registration', 'AB12CDE')->count());
        // No credit touched - nothing was actually generated.
        $this->assertSame(5, app(CreditLedgerService::class)->balance($user, VehicleCheck::TYPE_PLUS));
    }

    public function test_a_completed_plus_check_outside_the_reuse_window_does_not_get_reused(): void
    {
        $user = User::factory()->create();
        app(CreditLedgerService::class)->grantFreeCredits($user, VehicleCheck::TYPE_PLUS, 5);
        $vehicle = Vehicle::factory()->create(['registration' => 'AB12CDE']);
        VehicleCheck::factory()->create([
            'user_id' => $user->id,
            'vehicle_id' => $vehicle->id,
            'type' => VehicleCheck::TYPE_PLUS,
            'status' => VehicleCheck::STATUS_COMPLETED,
            'registration' => 'AB12CDE',
            'created_at' => now()->subDays(31),
        ]);

        $result = $this->submit($user, 'AB12CDE');

        $this->assertSame(2, VehicleCheck::where('registration', 'AB12CDE')->count());
        $this->assertSame(4, app(CreditLedgerService::class)->balance($user, VehicleCheck::TYPE_PLUS));
    }

    public function test_another_users_check_for_the_same_vehicle_is_never_reused(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        app(CreditLedgerService::class)->grantFreeCredits($otherUser, VehicleCheck::TYPE_PLUS, 5);
        $vehicle = Vehicle::factory()->create(['registration' => 'AB12CDE']);
        VehicleCheck::factory()->create([
            'user_id' => $owner->id,
            'vehicle_id' => $vehicle->id,
            'type' => VehicleCheck::TYPE_PLUS,
            'status' => VehicleCheck::STATUS_COMPLETED,
            'registration' => 'AB12CDE',
        ]);

        $result = $this->submit($otherUser, 'AB12CDE');

        $this->assertSame($otherUser->id, $result->user_id);
        $this->assertSame(2, VehicleCheck::where('registration', 'AB12CDE')->count());
    }

    public function test_a_failed_check_for_the_same_vehicle_is_never_reused(): void
    {
        $user = User::factory()->create();
        app(CreditLedgerService::class)->grantFreeCredits($user, VehicleCheck::TYPE_PLUS, 5);
        $vehicle = Vehicle::factory()->create(['registration' => 'AB12CDE']);
        VehicleCheck::factory()->create([
            'user_id' => $user->id,
            'vehicle_id' => $vehicle->id,
            'type' => VehicleCheck::TYPE_PLUS,
            'status' => VehicleCheck::STATUS_FAILED,
            'registration' => 'AB12CDE',
        ]);

        $result = $this->submit($user, 'AB12CDE');

        $this->assertSame(2, VehicleCheck::where('registration', 'AB12CDE')->count());
    }

    public function test_the_reuse_window_is_configurable(): void
    {
        config(['valecheck.reports.reuse_window_days' => 7]);
        $user = User::factory()->create();
        app(CreditLedgerService::class)->grantFreeCredits($user, VehicleCheck::TYPE_PLUS, 5);
        $vehicle = Vehicle::factory()->create(['registration' => 'AB12CDE']);
        $existing = VehicleCheck::factory()->create([
            'user_id' => $user->id,
            'vehicle_id' => $vehicle->id,
            'type' => VehicleCheck::TYPE_PLUS,
            'status' => VehicleCheck::STATUS_COMPLETED,
            'registration' => 'AB12CDE',
            'created_at' => now()->subDays(10),
        ]);

        $result = $this->submit($user, 'AB12CDE');

        $this->assertNotSame($existing->id, $result->id);
    }
}
