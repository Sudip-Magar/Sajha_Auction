<?php

use App\Models\Auction;
use App\Services\AuctionEngineService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(function (): void {
    Auction::query()
        ->with(['product', 'traditionalAuction'])
        ->where('status', 'active')
        ->where('end_time', '<=', now())
        ->eachById(function (Auction $auction): void {
            AuctionEngineService::checkAndFinalizeIfExpired($auction);
        });
})->everyMinute()->name('finalize-expired-auctions')->withoutOverlapping();
