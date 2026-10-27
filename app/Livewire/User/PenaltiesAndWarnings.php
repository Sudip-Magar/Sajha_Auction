<?php

namespace App\Livewire\User;

use App\Enums\DamagePenaltyStatus;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * The seller's own home for damage penalties (with a direct pay link while
 * one is unpaid) and warnings. Deliberately not gated behind is_seller -
 * a confirmed-damaged verdict revokes seller access, so this has to stay
 * reachable even after that happens; it's the only page besides the email
 * that shows a revoked seller how to get their access back.
 */
#[Layout('layouts.app')]
class PenaltiesAndWarnings extends Component
{
    public function mount(): void
    {
        if (! Auth::check()) {
            $this->redirect(route('user.login'), navigate: true);
        }
    }

    public function render(): View
    {
        $user = Auth::user();

        return view('livewire.user.penalties-and-warnings', [
            'penalties' => $user
                ? $user->damagePenalties()->with('order')->latest()->get()
                : collect(),
            'warnings' => $user
                ? $user->warnings()->with('product')->get()
                : collect(),
            'pendingStatus' => DamagePenaltyStatus::PENDING,
        ]);
    }
}
