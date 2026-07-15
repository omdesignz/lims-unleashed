<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SharedDocumentMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  array<string, string>  $document
     */
    public function __construct(
        public readonly string $mailSubject,
        public readonly string $mailMessage,
        public readonly array $document,
        private readonly string $pdfContent
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->mailSubject);
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.documents.shared');
    }

    /** @return array<int, Attachment> */
    public function attachments(): array
    {
        return [
            Attachment::fromData(fn (): string => $this->pdfContent, $this->document['filename'])
                ->withMime('application/pdf'),
        ];
    }
}
