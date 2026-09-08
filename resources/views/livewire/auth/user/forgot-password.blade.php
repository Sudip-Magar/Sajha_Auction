<div class="min-h-[calc(100vh-64px)] flex items-center justify-center bg-linear-to-br from-[#1F6F5F] to-[#2FA084] px-4 py-8">
    <div class="w-full max-w-4xl grid lg:grid-cols-2 bg-white/10 backdrop-blur-2xl rounded-[2.5rem] shadow-2xl border border-white/20 overflow-hidden">
        <div class="hidden lg:flex flex-col justify-center p-12 text-white bg-linear-to-br from-white/10 to-white/5 relative overflow-hidden">
            <div class="absolute top-0 right-0 w-32 h-32 bg-white/10 rounded-full -mr-16 -mt-16 blur-2xl"></div>
            <div class="absolute bottom-0 left-0 w-24 h-24 bg-[#2FA084]/20 rounded-full -ml-12 -mb-12 blur-xl"></div>

            <div class="w-20 h-20 rounded-2xl bg-white/10 flex items-center justify-center mb-6 border border-white/20 shadow-inner z-10">
                <x-icon name="o-key" class="w-10 h-10 text-white" />
            </div>

            <h2 class="text-4xl font-black tracking-tight mb-4 leading-tight z-10">Forgot Your<br/>Password?</h2>
            <p class="text-white/70 text-sm font-medium leading-relaxed max-w-xs z-10">No problem — enter your email and we'll send you a code to reset it.</p>
        </div>

        <div class="p-8 lg:p-12 bg-white/95 dark:bg-[#181A1F]">
            <div class="text-center mb-8">
                <h1 class="text-3xl font-black text-gray-900 tracking-tight dark:text-gray-100">Reset Password</h1>
                <p class="text-gray-500 text-sm mt-1 font-medium dark:text-gray-400">We'll email you a 6-digit code</p>
            </div>

            <form wire:submit="sendCode" class="space-y-5">
                <x-input
                    label="Email Address"
                    wire:model="email"
                    type="email"
                    icon="o-envelope"
                    placeholder="you@example.com"
                    class="bg-gray-50/50 border-gray-200 dark:bg-gray-800/50 dark:border-gray-700"
                />

                <x-button
                    label="Send Reset Code"
                    type="submit"
                    spinner="sendCode"
                    class="w-full bg-linear-to-r from-[#1F6F5F] to-[#2FA084] hover:shadow-lg hover:shadow-[#2FA084]/30 text-white border-none h-12 rounded-xl"
                />

                <div class="pt-6 text-center">
                    <a href="{{ route('user.login') }}" wire:navigate class="text-xs font-bold text-[#1F6F5F] hover:text-[#2FA084] transition-colors">
                        Back to Sign In
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
