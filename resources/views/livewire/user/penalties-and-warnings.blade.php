<div class="min-h-screen bg-gray-50/50 py-10 dark:bg-gray-900">
    <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
        <div class="border-b border-gray-200 pb-5 dark:border-gray-800">
            <h1 class="text-3xl font-black text-gray-900 dark:text-white flex items-center gap-3">
                <x-icon name="o-exclamation-triangle" class="w-8 h-8 text-[#1F6F5F]" />
                Penalties & Warnings
            </h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Any damage penalty on your account, and any warnings admins have issued you
            </p>
        </div>

        {{-- Damage Penalties --}}
        <div class="mt-8">
            <h2 class="text-lg font-black text-gray-900 dark:text-white mb-4">Damage Penalties</h2>

            @forelse($penalties as $penalty)
                @php
                    $penaltyCardClass = match($penalty->status) {
                        \App\Enums\DamagePenaltyStatus::PENDING => 'border-rose-200 bg-rose-50/60 dark:border-rose-900 dark:bg-rose-950/20',
                        \App\Enums\DamagePenaltyStatus::PAID => 'border-emerald-200 bg-emerald-50/60 dark:border-emerald-900 dark:bg-emerald-950/20',
                        default => 'border-gray-200 bg-gray-50 dark:border-gray-800 dark:bg-gray-800/40',
                    };
                    $penaltyBadgeClass = match($penalty->status) {
                        \App\Enums\DamagePenaltyStatus::PENDING => 'bg-rose-100 text-rose-700',
                        \App\Enums\DamagePenaltyStatus::PAID => 'bg-emerald-100 text-emerald-700',
                        default => 'bg-gray-200 text-gray-700',
                    };
                @endphp
                <div class="rounded-2xl border p-5 mb-4 {{ $penaltyCardClass }}">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div>
                            <p class="text-xs font-bold text-gray-500 uppercase tracking-wider">
                                Order #{{ $penalty->order?->order_number }}
                            </p>
                            <p class="text-lg font-black text-gray-900 dark:text-white mt-1">
                                Rs. {{ number_format($penalty->amount, 2) }}
                                <span class="ml-1 rounded-full px-3 py-1 text-xs font-extrabold {{ $penaltyBadgeClass }}">{{ $penalty->status->label() }}</span>
                            </p>
                            @if($penalty->status === $pendingStatus)
                                <p class="text-xs text-rose-700 dark:text-rose-400 mt-1 font-semibold">
                                    Due by {{ $penalty->due_at->format('M d, Y @ h:i A') }} — unpaid after the deadline permanently revokes your access and may lead to legal action.
                                </p>
                            @elseif($penalty->status === \App\Enums\DamagePenaltyStatus::PAID)
                                <p class="text-xs text-emerald-700 dark:text-emerald-400 mt-1">
                                    Paid {{ $penalty->paid_at?->format('M d, Y') }}. An admin will review and restore your access.
                                </p>
                            @elseif($penalty->status === \App\Enums\DamagePenaltyStatus::EXPIRED)
                                <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                                    This penalty expired unpaid on {{ $penalty->expired_at?->format('M d, Y') }}.
                                </p>
                            @endif
                        </div>

                        @if($penalty->status === $pendingStatus)
                            <a href="{{ route('payment.esewa.penalty-initiate', $penalty) }}" class="shrink-0 rounded-xl bg-[#60BB46] px-5 py-2.5 text-xs font-extrabold text-white hover:opacity-90 text-center">
                                Pay Penalty via eSewa
                            </a>
                        @endif
                    </div>
                </div>
            @empty
                <p class="text-sm text-gray-400">No damage penalties on your account.</p>
            @endforelse
        </div>

        {{-- Warnings --}}
        <div class="mt-10">
            <h2 class="text-lg font-black text-gray-900 dark:text-white mb-4">Warnings ({{ $warnings->count() }})</h2>

            @forelse($warnings as $warning)
                <div class="rounded-2xl border border-amber-200 bg-amber-50/60 p-4 mb-3 dark:border-amber-900 dark:bg-amber-950/20">
                    <p class="text-sm text-gray-800 dark:text-gray-200">{{ $warning->reason }}</p>
                    <p class="mt-1 text-[11px] font-bold uppercase tracking-wider text-amber-700 dark:text-amber-400">
                        {{ $warning->created_at->format('M d, Y @ h:i A') }}
                        @if($warning->product)
                            · Re: {{ $warning->product->name }}
                        @endif
                    </p>
                </div>
            @empty
                <p class="text-sm text-gray-400">No warnings issued.</p>
            @endforelse
        </div>
    </div>
</div>
