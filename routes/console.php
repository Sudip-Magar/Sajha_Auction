<?php

use App\Enums\AuctionStatus;
use App\Models\Auction;
use App\Services\AuctionEngineService;
use App\Services\DamagePenaltyService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(function (): void {
    Auction::query()
        ->with(['product', 'traditionalAuction'])
        ->where('status', AuctionStatus::ACTIVE)
        ->where('end_time', '<=', now())
        ->eachById(function (Auction $auction): void {
            AuctionEngineService::checkAndFinalizeIfExpired($auction);
        });
})->everyMinute()->name('finalize-expired-auctions')->withoutOverlapping();

Schedule::call(function (): void {
    DamagePenaltyService::expireOverdue();
})->hourly()->name('expire-overdue-damage-penalties')->withoutOverlapping();
