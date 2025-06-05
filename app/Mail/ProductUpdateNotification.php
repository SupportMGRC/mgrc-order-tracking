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
        // Check if this is a delivery update
        $isDeliveryUpdate = false;
        foreach ($this->updatedProducts as $product) {
            if ($product['name'] === 'Delivery Schedule') {
                $isDeliveryUpdate = true;
                break;
            }
        }

        $subject = $isDeliveryUpdate 
            ? "[DELIVERY UPDATED] Order #{$this->order->id} - Schedule Changed"
            : "[PRODUCT UPDATED] Order #{$this->order->id} - Products/Quantities Changed";

        return new Envelope(
            from: new \Illuminate\Mail\Mailables\Address(config('mail.from.address', 'support@mgrc.com'), config('mail.from.name', 'MGRC Order System')),
            subject: $subject,
            tags: ['order', 'update'],
            metadata: [
                'order_id' => $this->order->id,
                'update_type' => $isDeliveryUpdate ? 'delivery' : 'product',
            ]
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