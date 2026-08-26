<?php

namespace App\Mail;

use App\Models\VehicleCheck;
use App\Services\Reports\ReportPdfService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

/**
 * Not queued itself — this is only ever sent from inside GenerateReport,
 * which is already a queued job, so there's nothing to gain from queuing
 * the mailable too (and it would mean serializing the PDF bytes twice).
 */
class ReportReadyEmail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public VehicleCheck $vehicleCheck) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Your ValeCheck report for {$this->vehicleCheck->registration} is ready",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.report-ready',
            with: ['check' => $this->vehicleCheck],
        );
    }

    public function attachments(): array
    {
        $report = app(ReportPdfService::class)->generate($this->vehicleCheck);

        return [
            Attachment::fromData(
                fn () => Storage::disk($report->pdf_disk)->get($report->pdf_path),
                "ValeCheck-{$this->vehicleCheck->registration}.pdf",
            )->withMime('application/pdf'),
        ];
    }
}
