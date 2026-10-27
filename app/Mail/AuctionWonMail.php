<?php

namespace App\Mail;

use App\Models\Auction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Queued rather than sent inline: a live SMTP send used to sit on the
 * blocking path between an auction ending and the AuctionEnded broadcast
 * firing (both ran inside the same settlement transaction), delaying the
 * result on screen by however long the mail server took to respond.
 */
class AuctionWonMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Auction $auction) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Congratulations! You won an auction on Sajha Auction',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.auction-won',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
