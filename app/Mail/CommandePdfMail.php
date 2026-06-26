<?php

namespace App\Mail;

use App\Models\Commande;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CommandePdfMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Commande $commande,
        private string $pdfContent,
        private string $fileName
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Bon de commande ' . $this->commande->voucher_number
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.commandes.pdf'
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
