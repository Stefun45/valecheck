<?php

namespace Tests\Feature;

use App\Livewire\VehicleCheck\StartCheck;
use App\Models\DiscountCode;
use App\Models\User;
use App\Models\VehicleCheck;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DiscountCodeCheckoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_valid_code_shows_a_discounted_price_preview(): void
    {
        DiscountCode::create(['code' => 'SAVE10', 'type' => 'percentage', 'value' => 10]);

        Livewire::test(StartCheck::class)
            ->set('registration', 'AB12CDE')
            ->call('lookupVehicle')
            ->call('confirmVehicle', true)
            ->call('choose', 'check')
            ->set('discount_code', 'SAVE10')
            ->call('applyDiscountCode')
            ->assertSet('discountStatus', 'found')
            ->assertSee('8.09'); // £8.99 - 10%
    }

    public function test_an_invalid_code_shows_an_error_and_no_preview(): void
    {
        Livewire::test(StartCheck::class)
            ->set('registration', 'AB12CDE')
            ->call('lookupVehicle')
            ->call('confirmVehicle', true)
            ->call('choose', 'check')
            ->set('discount_code', 'NOTREAL')
            ->call('applyDiscountCode')
            ->assertSet('discountStatus', 'invalid')
            ->assertSet('discountPreview', null);
    }

    public function test_editing_the_code_after_a_successful_apply_resets_the_preview(): void
    {
        DiscountCode::create(['code' => 'SAVE10', 'type' => 'percentage', 'value' => 10]);

        Livewire::test(StartCheck::class)
            ->set('registration', 'AB12CDE')
            ->call('lookupVehicle')
            ->call('confirmVehicle', true)
            ->call('choose', 'check')
            ->set('discount_code', 'SAVE10')
            ->call('applyDiscountCode')
            ->assertSet('discountStatus', 'found')
            ->set('discount_code', 'SAVE10X')
            ->assertSet('discountStatus', 'idle')
            ->assertSet('discountPreview', null);
    }

    public function test_a_validated_code_is_carried_through_to_the_created_check(): void
    {
        DiscountCode::create(['code' => 'SAVE10', 'type' => 'percentage', 'value' => 10]);
        $user = User::factory()->create();
        $this->actingAs($user);

        Livewire::test(StartCheck::class)
            ->set('registration', 'AB12CDE')
            ->call('lookupVehicle')
            ->call('confirmVehicle', true)
            ->call('choose', 'check')
            ->set('discount_code', 'SAVE10')
            ->call('applyDiscountCode')
            ->call('submit');

        $check = VehicleCheck::where('registration', 'AB12CDE')->firstOrFail();
        $this->assertSame('SAVE10', $check->discount_code);
    }

    public function test_an_unvalidated_code_typed_but_never_applied_is_not_carried_through(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Livewire::test(StartCheck::class)
            ->set('registration', 'AB12CDE')
            ->call('lookupVehicle')
            ->call('confirmVehicle', true)
            ->call('choose', 'check')
            ->set('discount_code', 'NEVERAPPLIED')
            ->call('submit');

        $check = VehicleCheck::where('registration', 'AB12CDE')->firstOrFail();
        $this->assertNull($check->discount_code);
    }
}
