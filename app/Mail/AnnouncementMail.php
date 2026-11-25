<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AnnouncementMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $announcementSubject,
        public string $announcementBody
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->announcementSubject,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.announcement',
        );
    }
}
