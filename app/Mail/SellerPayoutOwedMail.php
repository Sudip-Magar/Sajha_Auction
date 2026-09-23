<?php

namespace App\Mail;

use App\Models\PayoutRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Queued for the same reason as AuctionWonMail: a live SMTP send must not
 * sit on the blocking path of the order-completion/cancellation transaction.
 */
class SellerPayoutOwedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public PayoutRequest $payout) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'A payout is ready for you on Sajha Auction',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.seller-payout-owed',
        );
    }
}
