<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LegalPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_guest_can_view_the_terms_page(): void
    {
        $this->get(route('legal.terms'))
            ->assertOk()
            ->assertSeeText('Terms & Conditions')
            ->assertSeeText('Silverback Customs UK Ltd')
            ->assertSeeText('data provided by Experian')
            // Per Experian's own review feedback: no need to name One
            // Auto (the underlying aggregator), and no data-guarantee
            // claim, since the APIs used don't come with one.
            ->assertDontSeeText('via One Auto API')
            ->assertDontSeeText('One Auto')
            ->assertDontSeeText('Experian mileage guarantee');
    }

    public function test_a_guest_can_view_the_privacy_page(): void
    {
        $this->get(route('legal.privacy'))
            ->assertOk()
            ->assertSeeText('Privacy Policy')
            ->assertSeeText('Silverback Customs UK Ltd');
    }

    public function test_the_business_identity_and_legal_links_appear_on_the_landing_page(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSeeText('Silverback Customs UK Ltd')
            ->assertSeeText('Unit 2A, 35 Eastgate North, Driffield, YO25 6DG')
            ->assertSee('href="'.route('legal.terms').'"', false)
            ->assertSee('href="'.route('legal.privacy').'"', false);
    }

    public function test_the_business_identity_appears_on_authenticated_pages_too(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSeeText('Silverback Customs UK Ltd');
    }

    public function test_the_business_identity_appears_on_guest_auth_pages(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSeeText('Silverback Customs UK Ltd');
    }
}
