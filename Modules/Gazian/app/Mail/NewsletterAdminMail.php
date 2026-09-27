<?php

namespace Modules\Gazian\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewsletterAdminMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public string $subscriberEmail) {}

    public function envelope(): Envelope
    {
        $brand = config('gazian.brand_name', 'Gazian Water');

        return new Envelope(
            subject: "[{$brand}] New newsletter subscriber",
            replyTo: [$this->subscriberEmail],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'gazian::emails.newsletter-admin',
            with: [
                'brand' => config('gazian.brand_name', 'Gazian Water'),
                'subscriberEmail' => $this->subscriberEmail,
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
