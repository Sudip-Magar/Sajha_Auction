<?php

namespace App\Livewire\Admin;

use App\Enums\StatusState;
use App\Models\User;
use App\Notifications\AccountStatusChangedNotification;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;

#[Layout('layouts.admin')]
class Users extends Component
{
    use Toast, WithPagination;

    public string $search = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function toggleStatus(User $user): void
    {
        $nextStatus = $user->isActiveStatus()
            ? StatusState::INACTIVE->value
            : StatusState::ACTIVE->value;

        $user->update([
            'status' => $nextStatus,
        ]);

        $user->notify(new AccountStatusChangedNotification($nextStatus));

        $this->success("User {$user->name} is now {$nextStatus}.");
    }

    public function render(): View
    {
        $users = User::query()
            ->withCount(['documentImages', 'products'])
            ->where(function ($query): void {
                $query
                    ->where('name', 'like', '%'.$this->search.'%')
                    ->orWhere('email', 'like', '%'.$this->search.'%')
                    ->orWhere('username', 'like', '%'.$this->search.'%')
                    ->orWhere('phone', 'like', '%'.$this->search.'%');
            })
            ->latest()
            ->paginate(12);

        return view('livewire.admin.users', [
            'users' => $users,
            'totalUsers' => User::count(),
            'activeUsers' => User::active()->count(),
            'inactiveUsers' => User::query()->whereRaw('LOWER(status) = ?', [StatusState::INACTIVE->value])->count(),
            'sellerUsers' => User::query()->where('is_seller', true)->count(),
            'auctionUsers' => User::query()->where('is_auction_allowed', true)->count(),
        ]);
    }
}
