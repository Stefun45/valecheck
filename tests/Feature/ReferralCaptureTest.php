<?php

namespace Tests\Feature;

use App\Models\Creator;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class ReferralCaptureTest extends TestCase
{
    use RefreshDatabase;

    public function test_visiting_a_link_with_ref_stores_it_in_the_session(): void
    {
        $this->get('/?ref=JANE10');

        $this->assertSame('JANE10', session('referral_code'));
    }

    public function test_registering_with_a_valid_referral_code_creates_a_referral(): void
    {
        $affiliateUser = User::factory()->create();
        $creator = Creator::create(['user_id' => $affiliateUser->id, 'name' => 'Jane', 'referral_code' => 'JANE10']);

        session(['referral_code' => 'JANE10']);

        $component = Volt::test('pages.auth.register')
            ->set('name', 'New Customer')
            ->set('email', 'newcustomer@example.com')
            ->set('password', 'password')
            ->set('password_confirmation', 'password')
            ->call('register');

        $component->assertRedirect(route('dashboard', absolute: false));

        $user = User::where('email', 'newcustomer@example.com')->firstOrFail();

        $this->assertDatabaseHas('referrals', [
            'creator_id' => $creator->id,
            'referred_user_id' => $user->id,
        ]);
        $this->assertNull(session('referral_code'), 'The session value should be consumed once used.');
    }

    public function test_registering_with_an_invalid_referral_code_still_succeeds_with_no_referral_created(): void
    {
        session(['referral_code' => 'DOES-NOT-EXIST']);

        Volt::test('pages.auth.register')
            ->set('name', 'New Customer')
            ->set('email', 'anothercustomer@example.com')
            ->set('password', 'password')
            ->set('password_confirmation', 'password')
            ->call('register')
            ->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticated();
        $this->assertDatabaseCount('referrals', 0);
    }

    public function test_registering_with_an_inactive_affiliate_code_creates_no_referral(): void
    {
        $affiliateUser = User::factory()->create();
        Creator::create(['user_id' => $affiliateUser->id, 'name' => 'Jane', 'referral_code' => 'JANE10', 'is_active' => false]);

        session(['referral_code' => 'JANE10']);

        Volt::test('pages.auth.register')
            ->set('name', 'New Customer')
            ->set('email', 'thirdcustomer@example.com')
            ->set('password', 'password')
            ->set('password_confirmation', 'password')
            ->call('register');

        $this->assertDatabaseCount('referrals', 0);
    }

    public function test_registering_with_no_referral_code_present_creates_no_referral(): void
    {
        Volt::test('pages.auth.register')
            ->set('name', 'New Customer')
            ->set('email', 'fourthcustomer@example.com')
            ->set('password', 'password')
            ->set('password_confirmation', 'password')
            ->call('register');

        $this->assertAuthenticated();
        $this->assertDatabaseCount('referrals', 0);
    }
}
