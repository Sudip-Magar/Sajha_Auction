<div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-[#1F6F5F] to-[#2FA084] px-4 py-12">
    <div class="w-full max-w-md">
        <div class="text-center mb-8 text-white">
            <h1 class="text-4xl font-extrabold tracking-tight drop-shadow-md">Welcome Back</h1>
            <p class="text-white/80 mt-2 font-medium">Log in to your Sajha Auction account</p>
        </div>

        <form wire:submit="login" class="bg-white/95 backdrop-blur-xl shadow-2xl rounded-3xl overflow-hidden transition-all duration-300 transform hover:scale-[1.01]">
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

                    <button type="submit"
                        wire:loading.attr="disabled"
                        class="w-full bg-gradient-to-r from-[#1F6F5F] to-[#2FA084] hover:shadow-lg hover:shadow-[#2FA084]/30 disabled:opacity-70 text-white font-bold py-3.5 rounded-xl transition-all duration-300 transform hover:-translate-y-0.5">
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

                <a href="{{ route('auth.google.redirect') }}" class="w-full bg-white border border-gray-200 hover:bg-gray-50 text-gray-700 shadow-sm transition-all duration-300 rounded-xl py-3.5 font-bold flex items-center justify-center gap-3">
                    <img src="{{ asset('assets/images/google-icon.png') }}" alt="google" class="w-5 h-5" /> 
                    <span>Sign in with Google</span>
                </a>
            </div>

            <div class="bg-gray-50/80 px-8 py-5 border-t border-gray-100 text-center">
                <p class="text-sm font-medium text-gray-600">
                    Don't have an account? 
                    <a href="/register" class="font-bold text-[#1F6F5F] hover:text-[#2FA084] transition-colors ml-1">Create one now</a>
                </p>
            </div>
        </form>
    </div>
</div>
