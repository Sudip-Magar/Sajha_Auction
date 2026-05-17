<?php

namespace App\Livewire\Admin;

use App\Models\User;
use App\Notifications\SellerApprovedNotification;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;

#[Layout('layouts.admin')]
class SellerRequests extends Component
{
    use Toast, WithPagination;

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

    public string $search = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function approveSeller(User $user): void
    {
        if (! $user->seller_application_pending || $user->is_seller) {
            $this->warning('This request is already processed.');

            return;
        }

        $user->update([
            'is_seller' => true,
            'seller_application_pending' => false,
        ]);

        $user->notify(new SellerApprovedNotification);

        $this->success("User {$user->name} is now a seller.");
    }

    public function render()
    {
        $users = User::query()
            ->where('seller_application_pending', true)
            ->where('is_seller', false)
            ->where(function ($query): void {
                $query
                    ->where('name', 'like', '%'.$this->search.'%')
                    ->orWhere('email', 'like', '%'.$this->search.'%');
            })
            ->latest()
            ->paginate(10);

        return view('livewire.admin.seller-requests', [
            'users' => $users,
            'pendingCount' => User::where('seller_application_pending', true)->where('is_seller', false)->count(),
            'approvedCount' => User::where('is_seller', true)->count(),
        ]);
    }
}
