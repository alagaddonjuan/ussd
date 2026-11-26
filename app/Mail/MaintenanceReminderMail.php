<?php

namespace App\Mail;

use App\Models\Client;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MaintenanceReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Client $client,
        public float $feeAmount
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Upcoming Monthly Maintenance Cycle — ' . config('app.name', 'Q4I Comms'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.maintenance-reminder',
        );
    }
}
