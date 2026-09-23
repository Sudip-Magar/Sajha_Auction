<?php

namespace App\Livewire\Admin;

use App\Enums\OrderComplaintStatus;
use App\Models\Order;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * A dedicated queue for orders with a buyer complaint (complaint_status is
 * not null), separate from the general order ledger at /admin/orders - lets
 * an admin triage complaints without wading through every other order.
 */
#[Layout('layouts.admin')]
class Complaints extends Component
{
    use WithPagination;

    public string $search = '';

    public string $statusFilter = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $complaints = Order::query()
            ->whereNotNull('complaint_status')
            ->with(['buyer', 'seller', 'items.product'])
            ->when($this->search, function ($query): void {
                $term = '%'.$this->search.'%';
                $query->where(function ($inner) use ($term): void {
                    $inner->where('order_number', 'like', $term)
                        ->orWhereHas('buyer', fn ($q) => $q->where('name', 'like', $term))
                        ->orWhereHas('seller', fn ($q) => $q->where('name', 'like', $term))
                        ->orWhereHas('items.product', fn ($q) => $q->where('name', 'like', $term));
                });
            })
            ->when($this->statusFilter, fn ($query) => $query->where('complaint_status', $this->statusFilter))
            ->latest()
            ->paginate(15);

        return view('livewire.admin.complaints', [
            'complaints' => $complaints,
            'statusOptions' => [
                ['id' => OrderComplaintStatus::UNDER_REVIEW->value, 'name' => 'Under Review'],
                ['id' => OrderComplaintStatus::CONFIRMED_DAMAGED->value, 'name' => 'Confirmed Damaged'],
                ['id' => OrderComplaintStatus::NOT_DAMAGED->value, 'name' => 'Not Damaged'],
            ],
            'totalComplaints' => Order::whereNotNull('complaint_status')->count(),
            'underReviewCount' => Order::where('complaint_status', OrderComplaintStatus::UNDER_REVIEW)->count(),
            'confirmedDamagedCount' => Order::where('complaint_status', OrderComplaintStatus::CONFIRMED_DAMAGED)->count(),
            'notDamagedCount' => Order::where('complaint_status', OrderComplaintStatus::NOT_DAMAGED)->count(),
        ]);
    }
}
