<?php

namespace App\Mail;

use App\Models\CoaEditRequest;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Sent to the QC staff who submitted (or uploaded) a COA when the COA
 * approver (QC HOD) returns it for correction. Carries the HOD's reason and
 * a link to where it is corrected: the COA page for a template COA, Order
 * Details for an uploaded file.
 */
class CoaReturnedNotification extends Mailable
{
    use SerializesModels;

    public $unlock;
    public $order;
    public $product;
    public $recipient;
    public $returnedBy;
    public $isUpload;

    public function __construct(CoaEditRequest $unlock, Order $order, Product $product, User $recipient, User $returnedBy, bool $isUpload)
    {
        $this->unlock     = $unlock;
        $this->order      = $order;
        $this->product    = $product;
        $this->recipient  = $recipient;
        $this->returnedBy = $returnedBy;
        $this->isUpload   = $isUpload;
    }

    public function envelope()
    {
        return new Envelope(
            from: new Address(
                config('mail.from.address', 'support@mgrc.com.my'),
                config('mail.from.name', 'MGRC Order System')
            ),
            subject: '[COA RETURNED] Order #' . $this->order->id . ' - ' . $this->product->name,
            tags: ['coa', 'coa-returned'],
            metadata: [
                'order_id'  => (string) $this->order->id,
                'unlock_id' => (string) $this->unlock->id,
            ]
        );
    }

    public function content()
    {
        return new Content(
            view: 'emails.coa-returned',
            with: [
                'unlock'     => $this->unlock,
                'order'      => $this->order,
                'product'    => $this->product,
                'recipient'  => $this->recipient,
                'returnedBy' => $this->returnedBy,
                'isUpload'   => $this->isUpload,
            ],
        );
    }

    public function attachments()
    {
        return [];
    }
}
