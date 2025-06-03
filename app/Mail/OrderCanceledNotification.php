<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderCanceledNotification extends Mailable
{
    use SerializesModels;

    public $order;

    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    public function envelope()
    {
        return new Envelope(
            from: new \Illuminate\Mail\Mailables\Address(config('mail.from.address', 'support@mgrc.com.my'), config('mail.from.name', 'MGRC Order System')),
            subject: '[CANCELED] Order #' . $this->order->id . ' has been canceled',
            tags: ['order', 'order-canceled'],
            metadata: [
                'order_id' => $this->order->id,
                'priority' => 'high',
            ]
        );
    }

    public function content()
    {
        return new Content(
            view: 'emails.order-canceled',
        );
    }

    public function attachments()
    {
        return [];
    }
} 