<?php

namespace App\Livewire\Admin;

use App\Models\DocumentImage;
use App\Models\User;
use App\Notifications\AuctionApplicationApprovedNotification;
use App\Notifications\AuctionApplicationRejectedNotification;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;

#[Layout('layouts.admin')]
class AuctionApplication extends Component
{
    use Toast, WithPagination;

    public string $search = '';

    public function getListeners(): array
    {
        $adminId = auth()->guard('admin')->id();

        if (! $adminId) {
            return [
                'adminNotificationReceived' => '$refresh',
            ];
        }

        return [
            'adminNotificationReceived' => '$refresh',
            "echo-notification:App.Models.Admin.{$adminId}" => '$refresh',
        ];
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function approveApplication(User $user): void
    {
        $documents = $user->documentImages()->get();

        if ($documents->isEmpty()) {
            $this->warning('No auction application documents were found for this user.');

            return;
        }

        if ($user->is_auction_allowed && $documents->every(fn (DocumentImage $document): bool => $document->is_approved)) {
            $this->warning('This auction application is already approved.');

            return;
        }

        $user->documentImages()->update([
            'is_approved' => true,
            'is_rejected' => false,
        ]);

        $user->update([
            'is_auction_allowed' => true,
        ]);

        $user->notify(new AuctionApplicationApprovedNotification);

        $this->success("Auction access approved for {$user->name}.");
    }

    public function rejectApplication(User $user): void
    {
        $documents = $user->documentImages()->get();

        if ($documents->isEmpty()) {
            $this->warning('No auction application documents were found for this user.');

            return;
        }

        if (! $user->is_auction_allowed && $documents->every(fn (DocumentImage $document): bool => $document->is_rejected)) {
            $this->warning('This auction application is already rejected.');

            return;
        }

        $user->documentImages()->update([
            'is_approved' => false,
            'is_rejected' => true,
        ]);

        $user->update([
            'is_auction_allowed' => false,
        ]);

        $user->notify(new AuctionApplicationRejectedNotification);

        $this->success("Auction access rejected for {$user->name}.");
    }

    public function applicationStatus(User $user): string
    {
        $documents = $user->documentImages;

        if ($user->is_auction_allowed || $documents->every(fn (DocumentImage $document): bool => $document->is_approved)) {
            return 'approved';
        }

        if ($documents->contains(fn (DocumentImage $document): bool => ! $document->is_approved && ! $document->is_rejected)) {
            return 'pending';
        }

        if ($documents->contains(fn (DocumentImage $document): bool => $document->is_rejected)) {
            return 'rejected';
        }

        return 'unknown';
    }

    public function render(): View
    {
        $applications = User::query()
            ->with('documentImages')
            ->whereHas('documentImages')
            ->where(function ($query): void {
                $query
                    ->where('name', 'like', '%'.$this->search.'%')
                    ->orWhere('email', 'like', '%'.$this->search.'%')
                    ->orWhere('username', 'like', '%'.$this->search.'%');
            })
            ->latest()
            ->paginate(10);

        $submittedUsers = User::query()
            ->with('documentImages')
            ->whereHas('documentImages')
            ->get();

        return view('livewire.admin.auction-application', [
            'applications' => $applications,
            'pendingCount' => $submittedUsers->filter(fn (User $user): bool => $this->applicationStatus($user) === 'pending')->count(),
            'approvedCount' => $submittedUsers->filter(fn (User $user): bool => $this->applicationStatus($user) === 'approved')->count(),
            'rejectedCount' => $submittedUsers->filter(fn (User $user): bool => $this->applicationStatus($user) === 'rejected')->count(),
        ]);
    }
}
