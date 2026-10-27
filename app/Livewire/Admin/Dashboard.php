<?php

namespace App\Livewire\Admin;

use App\Enums\OrderComplaintStatus;
use App\Enums\ProductApprovalStatus;
use App\Models\Category;
use App\Models\DamagePenalty;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin')]
class Dashboard extends Component
{
    public function render(): View
    {
        return view('livewire.admin.dashboard', [
            'totalUsers' => User::count(),
            'totalSellers' => User::where('is_seller', true)->count(),
            'pendingSellerRequests' => User::where('seller_application_pending', true)->where('is_seller', false)->count(),
            'pendingProducts' => Product::where('approval_status', ProductApprovalStatus::PENDING)->count(),
            'totalCategories' => Category::count(),
            'recentSellerRequests' => User::query()
                ->where('seller_application_pending', true)
                ->where('is_seller', false)
                ->latest()
                ->take(5)
                ->get(),
            'recentPendingProducts' => Product::query()
                ->with(['user', 'category'])
                ->where('approval_status', ProductApprovalStatus::PENDING)
                ->latest()
                ->take(5)
                ->get(),
            'complaintsAwaitingVerdict' => Order::query()
                ->with(['buyer', 'seller'])
                ->where('complaint_status', OrderComplaintStatus::UNDER_REVIEW)
                ->latest()
                ->take(10)
                ->get(),
            'legalActionOrders' => DamagePenalty::query()
                ->with(['order', 'seller'])
                ->where('legal_action_flagged', true)
                ->latest()
                ->take(10)
                ->get(),
        ]);
    }
}
