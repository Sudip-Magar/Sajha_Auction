<div x-data x-init="$store.register.term = false"
    class="min-h-screen flex items-center justify-center bg-gradient-to-br from-[#1F6F5F] to-[#2FA084] px-4 py-12">
    <div class="w-full max-w-md">
        <div class="text-center mb-8 text-white">
            <h1 class="text-4xl font-extrabold tracking-tight drop-shadow-md">Sajha Auction</h1>
            <p class="text-white/80 mt-2 font-medium">Join the ultimate bidding experience</p>
        </div>

        <form wire:submit="verifyEmail"
            class="bg-white/95 backdrop-blur-xl shadow-2xl rounded-3xl overflow-hidden transition-all duration-300 transform hover:scale-[1.01]">
            <div class="p-8">
                <h2 class="text-2xl font-extrabold text-gray-800 tracking-tight mb-6 text-center">Create Account</h2>
                <div class="px-3 text-center">
                    <p class="text-xs text-red-500">Kindly read the terms and policy before checking the box and procced
                        further.</p>
                </div>

                <div class="space-y-6">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">Email Address <span
                                class="text-red-500">*</span></label>
                        <div class="relative">
                            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">
                                <x-icon name="o-envelope" class="w-5 h-5" />
                            </span>
                            <input wire:model="email" type="email" placeholder="you@example.com"
                                class="w-full bg-gray-50/50 border border-gray-200 rounded-xl pl-11 pr-5 py-3 focus:outline-none focus:border-[#2FA084] focus:ring-2 focus:ring-[#2FA084]/20 transition-all font-medium text-gray-800" />
                        </div>
                        <span class="text-xs text-red-500 mt-1 inline-block">
                            @error('email')
                                {{ $message }}
                            @enderror
                        </span>
                    </div>

                    <label
                        class="flex items-start gap-3 bg-gray-50/50 p-3 rounded-xl border border-gray-100 cursor-pointer select-none">
                        <input type="checkbox" :checked="$store.register.term" autocomplete="off"
                            @change="$store.register.term = $event.target.checked"
                            class="checkbox checkbox-primary border-gray-300 mt-0.5" />
                        <span class="text-gray-700 font-medium text-sm">By creating and/or using your
                            account, you agree to our <a href="#" class="text-[#1F6F5F] font-bold hover:underline"
                                @click.stop>Terms of
                                Use</a> and <a href="#" class="text-[#1F6F5F] font-bold hover:underline"
                                @click.stop>Privacy
                                Policy</a>.</span>
                    </label>

                    <button type="submit" wire:loading.attr="disabled" x-bind:disabled="!$store.register.term"
                        class="w-full bg-linear-to-r from-[#1F6F5F] to-[#2FA084] hover:shadow-lg hover:shadow-[#2FA084]/30 disabled:opacity-70 text-white font-bold py-3.5 rounded-xl transition-all duration-300 transform hover:-translate-y-0.5"
                        :class="!$store.register.term ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer'">
                        <span wire:loading.remove wire:target="verifyEmail">Continue with Email</span>
                        <span wire:loading wire:target="verifyEmail" class="flex items-center justify-center gap-2">
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

                <button type="button" @click.prevent="$store.register.googleRedirect()"
                    class="w-full bg-white border border-gray-200 hover:bg-gray-50 text-gray-700 shadow-sm transition-all duration-300 rounded-xl py-3.5 font-bold flex items-center justify-center gap-3 cursor-pointer"
                    :class="!$store.register.term ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer'">
                    <img src="{{ asset('assets/images/google-icon.png') }}" alt="google" class="w-5 h-5" />
                    <span>Sign in with Google</span>
                </button>
            </div>

            <div class="bg-gray-50/80 px-8 py-5 border-t border-gray-100 text-center">
                <p class="text-sm font-medium text-gray-600">
                    Already have an account?
                    <a href="{{ route('user.login') }}"
                        class="font-bold text-[#1F6F5F] hover:text-[#2FA084] transition-colors ml-1">Sign in here</a>
                </p>
            </div>
        </form>
    </div>
</div>

@script
    <script>
        Alpine.store('register', {
            term: false,

            init() {
                this.term = false;

            },
            toggleTerm() {
                this.term = !this.term;
            },

            googleRedirect() {
                if (!this.term) return; // prevent if not checked
                window.location.href = "{{ route('auth.google.redirect') }}";
            },

        })
    </script>
@endscript
