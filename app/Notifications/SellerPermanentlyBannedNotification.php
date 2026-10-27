<?php

namespace App\Notifications;

use App\Models\User;

/**
 * Sent to admins when a seller hits the three-strikes threshold. Unlike an
 * ordinary revoked-pending-payment case, this ban is permanent and is not
 * cleared by the normal "restore access" action.
 */
class SellerPermanentlyBannedNotification extends BroadcastDatabaseNotification
{
    public function __construct(protected User $seller) {}

    public function toDatabase(object $notifiable): array
    {
        return [
            'message' => $this->seller->name.' has reached 3 confirmed-damage penalties and has been permanently banned from auctions and selling.',
            'type' => 'seller_permanently_banned',
            'seller_id' => $this->seller->id,
        ];
    }
}
