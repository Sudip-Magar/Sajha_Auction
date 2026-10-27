<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Queued for the same reason as AuctionWonMail: a live SMTP send must not
 * sit on the blocking path of the order-completion transaction.
 */
class OrderCompletedAdminMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Order $order) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Auction order #{$this->order->order_number} completed - payout needed",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.order-completed-admin',
        );
    }
}
