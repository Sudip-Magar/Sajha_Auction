<?php

namespace App\Livewire\Admin;

use App\Models\DocumentImage;
use App\Models\User;
use App\Notifications\AuctionApplicationApprovedNotification;
use App\Notifications\AuctionApplicationRejectedNotification;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
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

        // Auction access always requires seller access first (see
        // JoinAuction::submitApplication(), which bundles a seller request
        // in automatically) - approve that request before this one.
        if (! $user->is_seller) {
            $this->warning("{$user->name} does not have seller access yet. Approve their seller request first, on Seller Requests.");

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

    /**
     * Only applications still awaiting a decision belong on this queue - once
     * an admin approves or rejects one, it would otherwise sit here forever
     * (the query used to have no status filter at all). Rejecting doesn't
     * delete anything: resubmitting new document images through JoinAuction
     * resets those rows to pending, which naturally brings the applicant
     * back onto this list for another look.
     *
     * This can't be expressed as a single SQL where() (the same "pending"
     * definition applicationStatus() computes per user, from a mix of
     * document flags and is_auction_allowed), so it's filtered in PHP from
     * an eager-loaded set, same as the status counts below already were.
     */
    public function render(): View
    {
        $matchingUsers = User::query()
            ->with('documentImages')
            ->whereHas('documentImages')
            ->where(function ($query): void {
                $query
                    ->where('name', 'like', '%'.$this->search.'%')
                    ->orWhere('email', 'like', '%'.$this->search.'%')
                    ->orWhere('username', 'like', '%'.$this->search.'%');
            })
            ->latest()
            ->get()
            ->filter(fn (User $user): bool => $this->applicationStatus($user) === 'pending')
            ->values();

        $page = LengthAwarePaginator::resolveCurrentPage();
        $applications = new LengthAwarePaginator(
            $matchingUsers->forPage($page, 10)->values(),
            $matchingUsers->count(),
            10,
            $page,
            ['path' => LengthAwarePaginator::resolveCurrentPath()]
        );

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
