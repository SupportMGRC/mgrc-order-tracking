<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderPhotoUploadedNotification extends Mailable
{
    use Queueable, SerializesModels;

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
            subject: '[PHOTO UPLOADED] Order #' . $this->order->id
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
