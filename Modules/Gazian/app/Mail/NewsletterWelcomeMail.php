<?php

namespace Modules\Gazian\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewsletterWelcomeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public string $email) {}

    public function envelope(): Envelope
    {
        $brand = config('gazian.brand_name', 'Gazian Water');

        return new Envelope(
            subject: "You're on the list — {$brand}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'gazian::emails.newsletter-welcome',
            with: [
                'brand' => config('gazian.brand_name', 'Gazian Water'),
                'email' => $this->email,
            ],
        );
    }

    /**
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
