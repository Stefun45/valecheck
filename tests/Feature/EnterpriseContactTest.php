<?php

namespace Tests\Feature;

use App\Mail\EnterpriseEnquiryEmail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class EnterpriseContactTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_enterprise_contact_page_renders(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('contact.enterprise'))
            ->assertOk()
            ->assertSeeText('Enterprise enquiry');
    }

    public function test_submitting_the_form_emails_the_configured_enterprise_address(): void
    {
        Mail::fake();
        config(['valecheck.enterprise_contact_email' => 'sales@example.com']);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('contact.enterprise.submit'), [
                'name' => 'Jane Fleet',
                'email' => 'jane@fleetco.example',
                'company' => 'Fleet Co',
                'message' => 'We buy 200 vehicles a month, what can you offer?',
            ])
            ->assertRedirect()
            ->assertSessionHas('status');

        Mail::assertQueued(EnterpriseEnquiryEmail::class, function (EnterpriseEnquiryEmail $mail) {
            return $mail->hasTo('sales@example.com')
                && $mail->enquiry['name'] === 'Jane Fleet'
                && $mail->enquiry['email'] === 'jane@fleetco.example';
        });
    }

    public function test_submitting_without_a_message_fails_validation(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('contact.enterprise.submit'), [
                'name' => 'Jane Fleet',
                'email' => 'jane@fleetco.example',
                'message' => '',
            ])
            ->assertSessionHasErrors('message');
    }
}
