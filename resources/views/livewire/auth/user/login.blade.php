<div class="min-h-[calc(100vh-64px)] flex items-center justify-center bg-linear-to-br from-[#1F6F5F] to-[#2FA084] px-4 py-8">
    <div class="w-full max-w-4xl grid lg:grid-cols-2 bg-white/10 backdrop-blur-2xl rounded-[2.5rem] shadow-2xl border border-white/20 overflow-hidden">
        {{-- Left side: Branding/Image (Compact) --}}
        <div class="hidden lg:flex flex-col justify-center p-12 text-white bg-linear-to-br from-white/10 to-white/5 relative overflow-hidden">
            <div class="absolute top-0 right-0 w-32 h-32 bg-white/10 rounded-full -mr-16 -mt-16 blur-2xl"></div>
            <div class="absolute bottom-0 left-0 w-24 h-24 bg-[#2FA084]/20 rounded-full -ml-12 -mb-12 blur-xl"></div>

            <h2 class="text-4xl font-black tracking-tight mb-4 leading-tight z-10">Join the<br/>Sajha Auction.</h2>
            <p class="text-white/70 text-sm font-medium leading-relaxed max-w-xs z-10">Access the most trusted bidding platform in Nepal. Secure, fast, and transparent.</p>

            <div class="mt-8 flex items-center space-x-4 z-10">
                <div class="flex -space-x-2">
                    <div class="w-8 h-8 rounded-full border-2 border-white/20 bg-gray-400"></div>
                    <div class="w-8 h-8 rounded-full border-2 border-white/20 bg-gray-500"></div>
                    <div class="w-8 h-8 rounded-full border-2 border-white/20 bg-gray-600"></div>
                </div>
                <span class="text-xs font-bold text-white/50 tracking-widest uppercase">Trusted by 10k+</span>
            </div>
        </div>

        {{-- Right side: Form (Compact) --}}
        <div class="p-8 lg:p-12 bg-white/95 dark:bg-[#181A1F]">
            <div class="text-center mb-8">
                <h1 class="text-3xl font-black text-gray-900 tracking-tight dark:text-gray-100">Welcome Back</h1>
                <p class="text-gray-500 text-sm mt-1 font-medium dark:text-gray-400">Log in to your account</p>
            </div>

            <form wire:submit="login" class="space-y-5">
                <x-input
                    label="Email Address"
                    wire:model="email"
                    type="email"
                    icon="o-envelope"
                    placeholder="you@example.com"
                    class="bg-gray-50/50 border-gray-200 dark:bg-gray-800/50 dark:border-gray-700"
                />

                <x-password
                    label="Password"
                    wire:model="password"
                    placeholder="Enter your password"
                    class="bg-gray-50/50 border-gray-200 dark:bg-gray-800/50 dark:border-gray-700"
                />

                <div class="flex items-center justify-between pt-1">
                    <x-checkbox label="Remember me" wire:model="rememberMe" class="checkbox-primary text-xs font-bold" />
                    <a href="#" class="text-xs font-bold text-[#1F6F5F] hover:text-[#2FA084] transition-colors">Forgot Password?</a>
                </div>

                <x-button
                    label="Sign In"
                    type="submit"
                    spinner="login"
                    class="w-full bg-linear-to-r from-[#1F6F5F] to-[#2FA084] hover:shadow-lg hover:shadow-[#2FA084]/30 text-white border-none h-12 rounded-xl"
                />

                <div class="relative my-6">
                    <div class="absolute inset-0 flex items-center">
                        <div class="w-full border-t border-gray-200 dark:border-gray-800"></div>
                    </div>
                    <div class="relative flex justify-center text-[10px]">
                        <span class="px-4 bg-white text-gray-400 font-black tracking-widest uppercase dark:bg-[#181A1F] dark:text-gray-500">OR CONTINUE WITH</span>
                    </div>
                </div>

                <x-button
                    link="{{ route('auth.google.redirect') }}"
                    external
                    class="w-full bg-white border border-gray-200 hover:bg-gray-50 text-gray-700 h-12 rounded-xl dark:bg-[#181A1F] dark:border-gray-800 dark:text-gray-200 dark:hover:bg-gray-800"
                >
                    <img src="{{ asset('assets/images/google-icon.png') }}" alt="google" class="w-5 h-5 mr-2" />
                    <span class="font-bold">Google Account</span>
                </x-button>

                <div class="pt-6 text-center">
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400">
                        Don't have an account?
                        <a href="{{ route('user.register') }}" wire:navigate class="font-bold text-[#1F6F5F] hover:text-[#2FA084] transition-colors ml-1">Create one now</a>
                    </p>
                </div>
            </form>
        </div>
    </div>
</div>
