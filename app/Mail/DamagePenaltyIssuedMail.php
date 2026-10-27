<?php

namespace App\Mail;

use App\Models\DamagePenalty;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Queued for the same reason as AuctionWonMail: a live SMTP send must not
 * sit on the blocking path of the admin's verdict-recording transaction.
 */
class DamagePenaltyIssuedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public DamagePenalty $penalty) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Action required: damage penalty on your Sajha Auction order',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.damage-penalty-issued',
        );
    }
}
