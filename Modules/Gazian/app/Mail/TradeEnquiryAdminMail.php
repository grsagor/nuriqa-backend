<?php

namespace Modules\Gazian\Mail;

use Modules\Gazian\Models\TradeEnquiry;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TradeEnquiryAdminMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public TradeEnquiry $enquiry) {}

    public function envelope(): Envelope
    {
        $brand = config('gazian.brand_name', 'Gazian Water');

        return new Envelope(
            subject: "[{$brand}] New trade enquiry from {$this->enquiry->name}",
            replyTo: [$this->enquiry->email],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'gazian::emails.trade-enquiry-admin',
            with: [
                'brand' => config('gazian.brand_name', 'Gazian Water'),
                'enquiry' => $this->enquiry,
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
