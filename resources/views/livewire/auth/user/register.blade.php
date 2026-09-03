<div class="min-h-[calc(100vh-64px)] flex items-center justify-center bg-linear-to-br from-[#1F6F5F] to-[#2FA084] px-4 py-8">
    <div class="w-full max-w-4xl grid lg:grid-cols-2 bg-white/10 backdrop-blur-2xl rounded-[2.5rem] shadow-2xl border border-white/20 overflow-hidden">
        {{-- Left side: Branding (Compact) --}}
        <div class="hidden lg:flex flex-col justify-center p-12 text-white bg-linear-to-br from-white/10 to-white/5 relative overflow-hidden">
            <div class="absolute top-0 right-0 w-32 h-32 bg-white/10 rounded-full -mr-16 -mt-16 blur-2xl"></div>
            <div class="absolute bottom-0 left-0 w-24 h-24 bg-[#2FA084]/20 rounded-full -ml-12 -mb-12 blur-xl"></div>
            
            <h2 class="text-4xl font-black tracking-tight mb-4 leading-tight z-10">Start Your<br/>Bidding Journey.</h2>
            <p class="text-white/70 text-sm font-medium leading-relaxed max-w-xs z-10">Create an account to join the ultimate bidding experience in Nepal.</p>
            
            <div class="mt-8 flex items-center space-x-4 z-10">
                <div class="flex items-center text-xs font-black tracking-widest uppercase text-white/50 bg-white/10 px-3 py-1.5 rounded-full border border-white/10">
                    <x-icon name="o-shield-check" class="w-4 h-4 mr-2" /> Secure & Verified
                </div>
            </div>
        </div>

        {{-- Right side: Form (Compact) --}}
        <div class="p-8 lg:p-12 bg-white/95">
            <div class="text-center mb-6">
                <h1 class="text-3xl font-black text-gray-900 tracking-tight">Create Account</h1>
                <p class="text-gray-500 text-sm mt-1 font-medium">Join Sajha Auction today</p>
            </div>

            <form wire:submit="verifyEmail" class="space-y-5">
                <x-input 
                    label="Email Address" 
                    wire:model="email" 
                    type="email" 
                    icon="o-envelope" 
                    placeholder="you@example.com" 
                    class="bg-gray-50/50 border-gray-200"
                />

                <x-button
                    label="Continue with Email"
                    type="submit"
                    spinner="verifyEmail"
                    class="w-full bg-linear-to-r from-[#1F6F5F] to-[#2FA084] hover:shadow-lg hover:shadow-[#2FA084]/30 text-white border-none h-12 rounded-xl transition-all transform hover:-translate-y-0.5"
                />

                <div class="relative my-6">
                    <div class="absolute inset-0 flex items-center">
                        <div class="w-full border-t border-gray-200"></div>
                    </div>
                    <div class="relative flex justify-center text-[10px]">
                        <span class="px-4 bg-white text-gray-400 font-black tracking-widest uppercase">OR</span>
                    </div>
                </div>

                <x-button
                    link="{{ route('auth.google.redirect') }}"
                    external
                    class="w-full bg-white border border-gray-200 hover:bg-gray-50 text-gray-700 h-12 rounded-xl"
                >
                    <img src="{{ asset('assets/images/google-icon.png') }}" alt="google" class="w-5 h-5 mr-2" />
                    <span class="font-bold">Google Account</span>
                </x-button>

                <div class="pt-6 text-center border-t border-gray-100">
                    <p class="text-xs font-medium text-gray-500">
                        Already have an account?
                        <a href="{{ route('user.login') }}" wire:navigate class="font-bold text-[#1F6F5F] hover:text-[#2FA084] transition-colors ml-1">Sign in here</a>
                    </p>
                </div>
            </form>
        </div>
    </div>
</div>
