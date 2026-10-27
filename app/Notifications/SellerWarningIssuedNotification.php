<?php

namespace App\Notifications;

use App\Models\SellerWarning;

class SellerWarningIssuedNotification extends BroadcastDatabaseNotification
{
    public function __construct(public SellerWarning $warning) {}

    public function toDatabase(object $notifiable): array
    {
        return [
            'message' => 'An admin has issued you a warning: '.$this->warning->reason,
            'type' => 'seller_warning_issued',
            'warning_id' => $this->warning->id,
        ];
    }
}
