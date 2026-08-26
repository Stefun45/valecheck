<?php

namespace Tests\Feature;

use App\Models\DiscountCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDiscountCodeCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_non_admin_cannot_access_discount_code_admin_routes(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)->get(route('admin.discount-codes.index'))->assertForbidden();
    }

    public function test_an_admin_can_create_a_percentage_code(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)->post(route('admin.discount-codes.store'), [
            'code' => 'SAVE10',
            'type' => 'percentage',
            'value' => 10,
        ])->assertRedirect(route('admin.discount-codes.index'));

        $this->assertDatabaseHas('discount_codes', ['code' => 'SAVE10', 'type' => 'percentage']);
    }

    public function test_creating_a_code_without_one_specified_auto_generates_it(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)->post(route('admin.discount-codes.store'), [
            'type' => 'fixed',
            'value' => 5,
        ]);

        $this->assertSame(1, DiscountCode::count());
        $this->assertNotEmpty(DiscountCode::first()->code);
    }

    public function test_a_percentage_over_100_is_rejected(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)->post(route('admin.discount-codes.store'), [
            'code' => 'TOOMUCH',
            'type' => 'percentage',
            'value' => 150,
        ])->assertSessionHasErrors('value');

        $this->assertDatabaseMissing('discount_codes', ['code' => 'TOOMUCH']);
    }

    public function test_an_admin_can_toggle_a_codes_active_state(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $code = DiscountCode::create(['code' => 'TOGGLE', 'type' => 'fixed', 'value' => 1]);

        $this->actingAs($admin)->post(route('admin.discount-codes.toggle', $code));

        $this->assertFalse($code->fresh()->is_active);
    }

    public function test_an_admin_can_update_a_codes_value_and_restrictions(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $code = DiscountCode::create(['code' => 'EDITME', 'type' => 'fixed', 'value' => 1]);

        $this->actingAs($admin)->put(route('admin.discount-codes.update', $code), [
            'type' => 'percentage',
            'value' => 15,
            'applicable_products' => ['check'],
            'max_redemptions' => 100,
        ])->assertRedirect(route('admin.discount-codes.index'));

        $code->refresh();
        $this->assertSame('percentage', $code->type);
        $this->assertSame(['check'], $code->applicable_products);
        $this->assertSame(100, $code->max_redemptions);
    }
}
