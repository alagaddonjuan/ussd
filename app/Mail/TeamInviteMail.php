<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TeamInviteMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $teamName,
        public string $email,
        public ?string $temporaryPassword,
        public string $loginUrl
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Invitation to join {$this->teamName} on ".config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.team-invite',
        );
    }
}
