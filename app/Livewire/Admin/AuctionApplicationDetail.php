<?php

namespace App\Livewire\Admin;

use App\Models\DocumentImage;
use App\Models\User;
use App\Notifications\AuctionApplicationApprovedNotification;
use App\Notifications\AuctionApplicationRejectedNotification;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Mary\Traits\Toast;

#[Layout('layouts.admin')]
class AuctionApplicationDetail extends Component
{
    use Toast;

    public User $user;

    public function mount(User $user): void
    {
        $this->user = $user->load('documentImages');
    }

    public function approveApplication(): void
    {
        if ($this->user->documentImages->isEmpty()) {
            $this->warning('No auction application documents were found for this user.');

            return;
        }

        if ($this->user->is_auction_allowed && $this->user->documentImages->every(fn (DocumentImage $document): bool => $document->is_approved)) {
            $this->warning('This auction application is already approved.');

            return;
        }

        $this->user->documentImages()->update([
            'is_approved' => true,
            'is_rejected' => false,
        ]);

        $this->user->update([
            'is_auction_allowed' => true,
        ]);

        $this->user->notify(new AuctionApplicationApprovedNotification);
        $this->user->refresh()->load('documentImages');

        $this->success("Auction access approved for {$this->user->name}.");
    }

    public function rejectApplication(): void
    {
        if ($this->user->documentImages->isEmpty()) {
            $this->warning('No auction application documents were found for this user.');

            return;
        }

        if (! $this->user->is_auction_allowed && $this->user->documentImages->every(fn (DocumentImage $document): bool => $document->is_rejected)) {
            $this->warning('This auction application is already rejected.');

            return;
        }

        $this->user->documentImages()->update([
            'is_approved' => false,
            'is_rejected' => true,
        ]);

        $this->user->update([
            'is_auction_allowed' => false,
        ]);

        $this->user->notify(new AuctionApplicationRejectedNotification);
        $this->user->refresh()->load('documentImages');

        $this->success("Auction access rejected for {$this->user->name}.");
    }

    public function applicationStatus(): string
    {
        if ($this->user->is_auction_allowed || $this->user->documentImages->every(fn (DocumentImage $document): bool => $document->is_approved)) {
            return 'approved';
        }

        if ($this->user->documentImages->contains(fn (DocumentImage $document): bool => ! $document->is_approved && ! $document->is_rejected)) {
            return 'pending';
        }

        if ($this->user->documentImages->contains(fn (DocumentImage $document): bool => $document->is_rejected)) {
            return 'rejected';
        }

        return 'unknown';
    }

    public function render(): View
    {
        return view('livewire.admin.auction-application-detail');
    }
}
