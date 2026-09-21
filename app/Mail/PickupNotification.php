<?php

namespace App\Mail;

use App\Models\Pickup;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * One email class for every pickup event. The wording per event comes from
 * PickupNotifier::EVENTS.
 */
class PickupNotification extends Mailable
{
    use SerializesModels;

    public $pickup;
    public $event;
    public $copy;
    public $recipientName;
    public $reason;

    public function __construct(Pickup $pickup, string $event, array $copy, ?string $recipientName = null, string $reason = '')
    {
        $this->pickup        = $pickup;
        $this->event         = $event;
        $this->copy          = $copy;
        $this->recipientName = $recipientName;
        $this->reason        = $reason;
    }

    public function envelope()
    {
        return new Envelope(
            from: new Address(
                config('mail.from.address', 'support@mgrc.com.my'),
                config('mail.from.name', 'MGRC Order System')
            ),
            subject: '[PICKUP] ' . $this->pickup->reference_no . ' - ' . $this->copy['subject'],
            tags: ['pickup', 'pickup-' . $this->event],
            metadata: [
                'pickup_id' => (string) $this->pickup->id,
                'event'     => $this->event,
            ]
        );
    }

    public function content()
    {
        return new Content(
            view: 'emails.pickup-notification',
            with: [
                'pickup' => $this->pickup,
                'event'  => $this->event,
                'copy'          => $this->copy,
                'recipientName' => $this->recipientName,
                'reason'        => $this->reason,
            ],
        );
    }

    public function attachments()
    {
        return [];
    }
}
