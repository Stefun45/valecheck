<?php

namespace App\Mail;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Queued directly (no listener wraps this one) so the contact form's HTTP
 * response isn't held up waiting on the mail API call.
 */
class EnterpriseEnquiryEmail extends Mailable implements ShouldQueue
{
    use SerializesModels;

    /**
     * @param  array{name: string, email: string, company: ?string, message: string}  $enquiry
     */
    public function __construct(public array $enquiry) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New Enterprise enquiry — '.$this->enquiry['name'],
            replyTo: [$this->enquiry['email']],
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.enterprise-enquiry');
    }
}
