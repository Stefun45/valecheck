<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Not queued itself — SendWelcomeEmail (the listener that constructs and
 * sends this) already implements ShouldQueue, so the registration request
 * isn't held up either way. Queuing both would just double-defer it.
 */
class WelcomeEmail extends Mailable
{
    use SerializesModels;

    public function __construct(public User $user) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Welcome to ValeCheck');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.welcome');
    }
}
