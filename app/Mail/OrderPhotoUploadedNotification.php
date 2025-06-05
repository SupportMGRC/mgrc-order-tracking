<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderPhotoUploadedNotification extends Mailable
{
    use SerializesModels;

    public $order;
    public $markReadyUrl;

    /**
     * Create a new message instance.
     */
    public function __construct($order, $markReadyUrl)
    {
        $this->order = $order;
        $this->markReadyUrl = $markReadyUrl;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            from: new \Illuminate\Mail\Mailables\Address(config('mail.from.address', 'support@mgrc.com'), config('mail.from.name', 'MGRC Order System')),
            subject: '[PHOTO UPLOADED] Order #' . $this->order->id . ' - Photo Available',
            tags: ['order', 'photo-uploaded'],
            metadata: [
                'order_id' => $this->order->id,
                'priority' => 'normal',
            ]
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.order-photo-uploaded',
            with: [
                'order' => $this->order,
                'markReadyUrl' => $this->markReadyUrl,
            ]
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
