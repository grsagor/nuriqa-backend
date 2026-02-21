<?php

namespace App\Mail;

use App\Models\Withdrawal;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WithdrawalApproved extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Withdrawal $withdrawal
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Withdrawal Approved: $'.number_format($this->withdrawal->amount, 2),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.withdrawal-approved',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
