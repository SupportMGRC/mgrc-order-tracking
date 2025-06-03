<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ProductUpdateNotification extends Mailable
{
    use SerializesModels;

    /**
     * The order instance.
     *
     * @var \App\Models\Order
     */
    public $order;
    
    /**
     * The updated products data.
     *
     * @var array
     */
    public $updatedProducts;

    /**
     * Create a new message instance.
     *
     * @param  \App\Models\Order  $order
     * @param  array  $updatedProducts
     * @return void
     */
    public function __construct(Order $order, array $updatedProducts)
    {
        $this->order = $order;
        $this->updatedProducts = $updatedProducts;
    }

    /**
     * Get the message envelope.
     *
     * @return \Illuminate\Mail\Mailables\Envelope
     */
    public function envelope()
    {
        return new Envelope(
            subject: "[PRODUCT UPDATED] Order #{$this->order->id} - Products/Quantities Changed",
        );
    }

    /**
     * Get the message content definition.
     *
     * @return \Illuminate\Mail\Mailables\Content
     */
    public function content()
    {
        return new Content(
            view: 'emails.product-update',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array
     */
    public function attachments()
    {
        return [];
    }
} 