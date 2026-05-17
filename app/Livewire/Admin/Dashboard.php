<?php

namespace App\Livewire\Admin;

use App\Models\Category;
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
            'pendingProducts' => Product::where('is_approved', false)->count(),
            'totalCategories' => Category::count(),
            'recentSellerRequests' => User::query()
                ->where('seller_application_pending', true)
                ->where('is_seller', false)
                ->latest()
                ->take(5)
                ->get(),
            'recentPendingProducts' => Product::query()
                ->with(['user', 'category'])
                ->where('is_approved', false)
                ->latest()
                ->take(5)
                ->get(),
        ]);
    }
}
