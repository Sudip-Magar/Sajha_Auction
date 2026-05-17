<div class="min-h-screen bg-linear-to-b from-[#F6FFFB] via-white to-[#ECF8F3]">
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-16 pb-20">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-10 items-center">
            <div class="space-y-6">
                <p class="inline-flex items-center rounded-full bg-[#2FA084]/10 px-4 py-1.5 text-xs font-black uppercase tracking-[0.2em] text-[#1F6F5F]">
                    Live marketplace
                </p>
                <div class="space-y-4">
                    <h1 class="text-4xl sm:text-5xl lg:text-6xl font-black tracking-tight text-gray-900 leading-tight">
                        Auction, sell, and discover valuable items in one place.
                    </h1>
                    <p class="max-w-2xl text-base sm:text-lg text-gray-600 leading-relaxed">
                        Sajha Auction gives buyers and sellers a clean real-time marketplace with transparent approvals, fast alerts, and streamlined product management.
                    </p>
                </div>
                <div class="flex flex-wrap gap-3">
                    <a href="{{ route('user.register') }}" wire:navigate class="inline-flex items-center justify-center rounded-2xl bg-linear-to-r from-[#1F6F5F] to-[#2FA084] px-6 py-3 text-sm font-black text-white shadow-lg shadow-[#2FA084]/20">
                        Get Started
                    </a>
                    <a href="{{ route('user.login') }}" wire:navigate class="inline-flex items-center justify-center rounded-2xl border border-gray-200 bg-white px-6 py-3 text-sm font-bold text-gray-700">
                        Sign In
                    </a>
                </div>
            </div>

            <div class="relative">
                <div class="absolute -top-6 -left-6 h-28 w-28 rounded-full bg-[#2FA084]/15 blur-3xl"></div>
                <div class="absolute -bottom-8 -right-8 h-32 w-32 rounded-full bg-[#1F6F5F]/10 blur-3xl"></div>
                <div class="relative overflow-hidden rounded-[2rem] border border-[#2FA084]/10 bg-white p-6 shadow-2xl shadow-[#1F6F5F]/8">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="rounded-3xl bg-[#0F2C24] p-5 text-white">
                            <p class="text-xs uppercase tracking-[0.2em] text-white/60">Realtime</p>
                            <p class="mt-3 text-3xl font-black">24/7</p>
                            <p class="mt-2 text-sm text-white/70">Instant notification updates for sellers and admins.</p>
                        </div>
                        <div class="rounded-3xl bg-[#F4FBF8] p-5">
                            <p class="text-xs uppercase tracking-[0.2em] text-[#1F6F5F]/60">Approvals</p>
                            <p class="mt-3 text-3xl font-black text-[#1F6F5F]">Fast</p>
                            <p class="mt-2 text-sm text-gray-600">Products and seller requests move through one clear workflow.</p>
                        </div>
                        <div class="col-span-2 rounded-3xl border border-dashed border-[#2FA084]/25 bg-linear-to-r from-[#F5FFFB] to-[#ECF8F3] p-5">
                            <div class="flex items-center justify-between gap-4">
                                <div>
                                    <p class="text-xs uppercase tracking-[0.2em] text-[#1F6F5F]/60">Start selling</p>
                                    <p class="mt-2 text-2xl font-black text-gray-900">Upload products after approval and manage them from your dashboard.</p>
                                </div>
                                <div class="hidden sm:flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl bg-[#2FA084] text-white shadow-lg shadow-[#2FA084]/25">
                                    <x-icon name="o-bolt" class="w-8 h-8" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
