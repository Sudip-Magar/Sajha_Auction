<?php

namespace App\Livewire\Admin;

use App\Models\Order;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.admin')]
class Orders extends Component
{
    use WithPagination;

    public string $search = '';

    public string $statusFilter = '';

    public string $typeFilter = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingTypeFilter(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $orders = Order::query()
            ->with(['buyer', 'seller', 'items.product', 'auction'])
            ->when($this->search, function ($query): void {
                $term = '%'.$this->search.'%';
                $query->where(function ($inner) use ($term): void {
                    $inner->where('order_number', 'like', $term)
                        ->orWhereHas('buyer', fn ($q) => $q->where('name', 'like', $term))
                        ->orWhereHas('seller', fn ($q) => $q->where('name', 'like', $term))
                        ->orWhereHas('items.product', fn ($q) => $q->where('name', 'like', $term));
                });
            })
            ->when($this->statusFilter, fn ($query) => $query->where('status', $this->statusFilter))
            ->when($this->typeFilter === 'auction', fn ($query) => $query->whereNotNull('auction_id'))
            ->when($this->typeFilter === 'direct_sell', fn ($query) => $query->whereNull('auction_id'))
            ->latest()
            ->paginate(15);

        return view('livewire.admin.orders', [
            'orders' => $orders,
            'statusOptions' => [
                ['id' => 'pending', 'name' => 'Pending Confirmation'],
                ['id' => 'confirmed', 'name' => 'Confirmed'],
                ['id' => 'meetup_scheduled', 'name' => 'Meetup Scheduled'],
                ['id' => 'completed', 'name' => 'Completed & Sold'],
                ['id' => 'cancelled', 'name' => 'Cancelled'],
            ],
            'typeOptions' => [
                ['id' => 'direct_sell', 'name' => 'Second-Hand (Direct Sell)'],
                ['id' => 'auction', 'name' => 'Auction'],
            ],
            'totalOrders' => Order::count(),
            'completedOrders' => Order::where('status', 'completed')->count(),
            'cancelledOrders' => Order::where('status', 'cancelled')->count(),
            'refundsOwed' => Order::where('deposit_status', 'refund_owed')->count(),
        ]);
    }
}
