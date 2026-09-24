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
 * Sent to each COA approver (QC HOD) when Quality Control asks for a
 * submitted COA to be unlocked. The approver decides on the COA page.
 */
class CoaEditRequestNotification extends Mailable
{
    use SerializesModels;

    public $editRequest;
    public $order;
    public $product;
    public $requester;
    public $approver;

    public function __construct(CoaEditRequest $editRequest, Order $order, Product $product, User $requester, User $approver)
    {
        $this->editRequest = $editRequest;
        $this->order       = $order;
        $this->product     = $product;
        $this->requester   = $requester;
        $this->approver    = $approver;
    }

    public function envelope()
    {
        return new Envelope(
            from: new Address(
                config('mail.from.address', 'support@mgrc.com.my'),
                config('mail.from.name', 'MGRC Order System')
            ),
            subject: '[COA EDIT REQUEST] Order #' . $this->order->id . ' - ' . $this->product->name,
            tags: ['coa', 'coa-edit-request'],
            metadata: [
                'order_id'   => (string) $this->order->id,
                'request_id' => (string) $this->editRequest->id,
            ]
        );
    }

    public function content()
    {
        return new Content(
            view: 'emails.coa-edit-request',
            with: [
                'editRequest' => $this->editRequest,
                'order'       => $this->order,
                'product'     => $this->product,
                'requester'   => $this->requester,
                'approver'    => $this->approver,
            ],
        );
    }

    public function attachments()
    {
        return [];
    }
}
