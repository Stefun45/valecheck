<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserProductPrice;
use App\Models\VehicleCheck;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_non_admin_cannot_access_the_user_list_or_pricing_pages(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)->get(route('admin.users.index'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.users.edit-prices', $user))->assertForbidden();
    }

    public function test_an_admin_can_see_the_user_list_with_a_check_count(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $customer = User::factory()->create(['name' => 'Jane Dealer', 'email' => 'jane@example.com']);
        VehicleCheck::factory()->create(['user_id' => $customer->id]);

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSeeText('Jane Dealer')
            ->assertSeeText('jane@example.com')
            ->assertSeeText('Standard');
    }

    public function test_the_user_list_can_be_searched_by_name_or_email(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        User::factory()->create(['name' => 'Findable Fred', 'email' => 'fred@example.com']);
        User::factory()->create(['name' => 'Someone Else', 'email' => 'else@example.com']);

        $this->actingAs($admin)
            ->get(route('admin.users.index', ['search' => 'Findable']))
            ->assertOk()
            ->assertSeeText('Findable Fred')
            ->assertDontSeeText('Someone Else');
    }

    public function test_an_admin_can_set_a_custom_price_for_one_account(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $customer = User::factory()->create();

        $this->actingAs($admin)
            ->put(route('admin.users.update-prices', $customer), [
                'check' => '3.50',
                'plus' => '',
                'rebuild' => '',
            ])
            ->assertRedirect(route('admin.users.index'));

        $this->assertSame('3.50', UserProductPrice::where('user_id', $customer->id)->where('type', 'check')->value('gross'));
        $this->assertSame(0, UserProductPrice::where('user_id', $customer->id)->where('type', 'plus')->count());
    }

    public function test_an_admin_can_set_a_custom_price_of_zero(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $customer = User::factory()->create();

        $this->actingAs($admin)->put(route('admin.users.update-prices', $customer), [
            'check' => '0',
            'plus' => '',
            'rebuild' => '',
        ]);

        // Stored as a real 0 override, not cleared — distinct from
        // submitting an empty field, which deletes the override entirely.
        $this->assertSame(1, UserProductPrice::where('user_id', $customer->id)->where('type', 'check')->count());
        $this->assertSame('0.00', UserProductPrice::where('user_id', $customer->id)->where('type', 'check')->value('gross'));
    }

    public function test_clearing_a_previously_set_custom_price_reverts_to_standard(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $customer = User::factory()->create();
        UserProductPrice::create(['user_id' => $customer->id, 'type' => 'check', 'gross' => 3.50]);

        $this->actingAs($admin)->put(route('admin.users.update-prices', $customer), [
            'check' => '',
            'plus' => '',
            'rebuild' => '',
        ]);

        $this->assertSame(0, UserProductPrice::where('user_id', $customer->id)->count());
    }

    public function test_the_user_list_flags_accounts_with_custom_pricing(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $customer = User::factory()->create(['name' => 'Custom Carl']);
        UserProductPrice::create(['user_id' => $customer->id, 'type' => 'plus', 'gross' => 5.00]);

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSeeText('Custom Carl')
            ->assertSeeText('Plus');
    }
}
