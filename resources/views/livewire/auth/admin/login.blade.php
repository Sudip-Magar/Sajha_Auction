<div class="min-h-screen flex items-center justify-center bg-linear-to-br from-[#1F6F5F] to-[#2FA084] px-4 py-12">
    <div class="w-full max-w-md">
        <div class="text-center mb-8 text-white">
            <h1 class="text-4xl font-extrabold tracking-tight drop-shadow-md">Welcome Back Admin</h1>
            <p class="text-white/80 mt-2 font-medium">Log in to your Sajha Auction account</p>
        </div>

        <form wire:submit="login"
            class="bg-white/95 backdrop-blur-xl shadow-2xl rounded-3xl overflow-hidden transition-all duration-300 transform hover:scale-[1.01]">
            <div class="p-8">
                <div class="space-y-6">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">Email Address</label>
                        <div class="relative">
                            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">
                                <x-icon name="o-envelope" class="w-5 h-5" />
                            </span>
                            <input wire:model="email" type="email" placeholder="you@example.com"
                                class="w-full bg-gray-50/50 border border-gray-200 rounded-xl pl-11 pr-5 py-3 focus:outline-none focus:border-[#2FA084] focus:ring-2 focus:ring-[#2FA084]/20 transition-all font-medium text-gray-800" />
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">Password</label>
                        <div class="relative">
                            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">
                                <x-icon name="o-key" class="w-5 h-5" />
                            </span>
                            <input wire:model="password" :type="$store.login.isVisible ? 'text' : 'password'"
                                placeholder="Enter your password"
                                class="w-full bg-gray-50/50 border border-gray-200 rounded-xl pl-11 pr-5 py-3 focus:outline-none focus:border-[#2FA084] focus:ring-2 focus:ring-[#2FA084]/20 transition-all font-medium text-gray-800" />
                            <button type="button" @click="$store.login.toggleVisible()"
                                class="cursor-pointer absolute right-3 top-1/2 -translate-y-1/2 duration-150">
                                <template x-if="$store.login.isVisible">
                                    <x-icon name="o-eye" class="w-4 h-4 text-gray-500" />
                                </template>
                                <template x-if="!$store.login.isVisible">
                                    <x-icon name="o-eye-slash" class="w-4 h-4 text-gray-500" />
                                </template>
                            </button>
                        </div>
                    </div>

                    <button type="submit" wire:loading.attr="disabled"
                        class="w-full bg-linear-to-r from-[#1F6F5F] to-[#2FA084] hover:shadow-lg hover:shadow-[#2FA084]/30 disabled:opacity-70 text-white font-bold py-3.5 rounded-xl transition-all duration-300 transform hover:-translate-y-0.5">
                        <span wire:loading.remove wire:target="login">Sign In</span>
                        <span wire:loading wire:target="login" class="flex items-center justify-center gap-2">
                            <x-icon name="o-arrow-path" class="w-5 h-5 animate-spin" /> Processing...
                        </span>
                    </button>
                </div>

                <div class="relative my-8">
                    <div class="absolute inset-0 flex items-center">
                        <div class="w-full border-t border-gray-200"></div>
                    </div>
                    <div class="relative flex justify-center text-sm">
                        <span class="px-4 bg-white text-gray-400 font-medium">OR</span>
                    </div>
                </div>
            </div>

        </form>
    </div>
</div>

@script
<script>
    Alpine.store('login', {
        isVisible: false,
        toggleVisible() {
            this.isVisible = !this.isVisible;
        }
    });
</script>
@endscript