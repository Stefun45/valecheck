<?php

namespace Tests\Feature;

use App\Mail\WelcomeEmail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Volt\Volt;
use Tests\TestCase;

class WelcomeEmailTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_welcome_email_is_sent_on_registration(): void
    {
        Mail::fake();

        Volt::test('pages.auth.register')
            ->set('name', 'New Customer')
            ->set('email', 'welcome-test@example.com')
            ->set('password', 'password')
            ->set('password_confirmation', 'password')
            ->call('register');

        $user = User::where('email', 'welcome-test@example.com')->firstOrFail();

        Mail::assertSent(WelcomeEmail::class, fn (WelcomeEmail $mail) => $mail->hasTo($user->email));

        // Registration must fire exactly one welcome email — Laravel
        // auto-discovers listeners with a handle(Event) method, so an
        // explicit Event::listen() for the same pair registers it twice
        // and silently doubles every send.
        Mail::assertSent(WelcomeEmail::class, 1);
    }
}
