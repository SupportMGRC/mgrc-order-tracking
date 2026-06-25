<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * OrderStatusUpdateNotification
 *
 * Sent to the person who CREATED the order whenever the order's status
 * changes to one of the tracked states (ready / delivered / preparing).
 *
 * The human-friendly label (e.g. "Ready for Delivery") is passed in so the
 * same email class can be reused for every status.
 */
class OrderStatusUpdateNotification extends Mailable
{
    use SerializesModels;

    /** @var \App\Models\Order */
    public $order;

    /** @var string  The raw status value, e.g. "ready" */
    public $status;

    /** @var string  The friendly label shown to the user, e.g. "Ready for Delivery" */
    public $statusLabel;

    /** @var array  Optional old->new schedule changes for schedule-update emails */
    public $scheduleChanges;

    public function __construct(Order $order, string $status, string $statusLabel, array $scheduleChanges = [])
    {
        $this->order           = $order;
        $this->status          = $status;
        $this->statusLabel     = $statusLabel;
        $this->scheduleChanges = $scheduleChanges;
    }

    public function envelope()
    {
        return new Envelope(
            from: new \Illuminate\Mail\Mailables\Address(
                config('mail.from.address', 'support@mgrc.com.my'),
                config('mail.from.name', 'MGRC Order System')
            ),
            subject: '[ORDER UPDATE] Order #' . $this->order->id . ' - ' . $this->statusLabel,
            tags: ['order', 'order-status-update'],
            metadata: [
                'order_id' => (string) $this->order->id,
                'status'   => $this->status,
            ]
        );
    }

    public function content()
    {
        return new Content(
            view: 'emails.order-status-update',
            with: [
                'order'           => $this->order,
                'statusLabel'     => $this->statusLabel,
                'status'          => $this->status,
                'scheduleChanges' => $this->scheduleChanges,
            ],
        );
    }

    public function attachments()
    {
        return [];
    }
}
