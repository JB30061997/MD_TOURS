<?php

namespace App\Mail;

use App\Models\RoadSheet;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RoadSheetPdfMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public RoadSheet $roadSheet,
        private string $pdfContent,
        private string $fileName
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Roadsheet ' . ($this->roadSheet->voucher_number ?: $this->roadSheet->planning?->ref_dossier)
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.road-sheets.pdf'
        );
    }

    public function attachments(): array
    {
        return [
            Attachment::fromData(fn () => $this->pdfContent, $this->fileName)
                ->withMime('application/pdf'),
        ];
    }
}
