<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class GatewayAlertMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public int $errorCount,
        public int $windowMinutes,
        public ?string $latestOutcome = null
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "[Alert] {$this->errorCount} Gateway/Webhook Errors Detected — " . config('app.name', 'Q4I Comms'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.gateway-alert',
        );
    }
}
