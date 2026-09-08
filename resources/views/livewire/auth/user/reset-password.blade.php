<div x-data="{ timer: {{ $countdown }} }"
     x-init="setInterval(() => { if(timer > 0) timer-- }, 1000)"
     @start-countdown.window="timer = 30"
     class="min-h-[calc(100vh-64px)] flex items-center justify-center bg-linear-to-br from-[#1F6F5F] to-[#2FA084] px-4 py-8">

    <div class="w-full max-w-4xl grid lg:grid-cols-2 bg-white/10 backdrop-blur-2xl rounded-[2.5rem] shadow-2xl border border-white/20 overflow-hidden">
        <div class="hidden lg:flex flex-col justify-center p-12 text-white bg-linear-to-br from-white/10 to-white/5 relative overflow-hidden">
            <div class="absolute top-0 right-0 w-32 h-32 bg-white/10 rounded-full -mr-16 -mt-16 blur-2xl"></div>
            <div class="absolute bottom-0 left-0 w-24 h-24 bg-[#2FA084]/20 rounded-full -ml-12 -mb-12 blur-xl"></div>

            <div class="w-20 h-20 rounded-2xl bg-white/10 flex items-center justify-center mb-6 border border-white/20 shadow-inner z-10">
                <x-icon name="o-lock-closed" class="w-10 h-10 text-white" />
            </div>

            <h2 class="text-4xl font-black tracking-tight mb-4 leading-tight z-10">Check Your<br/>Inbox.</h2>
            <p class="text-white/70 text-sm font-medium leading-relaxed max-w-xs z-10">Enter the code we sent you and choose a new password.</p>
        </div>

        <div class="p-8 lg:p-12 bg-white/95 dark:bg-[#181A1F]">
            <div class="text-center mb-8">
                <h1 class="text-3xl font-black text-gray-900 tracking-tight dark:text-gray-100">Set New Password</h1>
                <p class="text-gray-500 text-sm mt-2 font-medium dark:text-gray-400">Code sent to <br><strong class="text-[#1F6F5F]">{{ $email }}</strong></p>
            </div>

            <form wire:submit="resetPassword" class="space-y-5">
                <div class="flex items-center justify-center" x-on:completed="">
                    <div class="p-3 bg-gray-50 rounded-2xl border border-gray-100 shadow-inner dark:bg-gray-800/50 dark:border-gray-700">
                        <x-pin wire:model="otp" size="6" numeric class="focus:border-[#2FA084] gap-2" />
                    </div>
                </div>

                <x-password
                    label="New Password"
                    wire:model="password"
                    placeholder="At least 8 characters"
                    class="bg-gray-50/50 border-gray-200 dark:bg-gray-800/50 dark:border-gray-700"
                />

                <x-password
                    label="Confirm New Password"
                    wire:model="password_confirmation"
                    placeholder="Re-enter your new password"
                    class="bg-gray-50/50 border-gray-200 dark:bg-gray-800/50 dark:border-gray-700"
                />

                <x-button
                    label="Reset Password"
                    type="submit"
                    spinner="resetPassword"
                    class="w-full bg-linear-to-r from-[#1F6F5F] to-[#2FA084] hover:shadow-lg hover:shadow-[#2FA084]/30 text-white border-none h-12 rounded-xl"
                />

                <div class="pt-2 text-center">
                    <button
                        type="button"
                        wire:click="resendCode"
                        x-bind:disabled="timer > 0"
                        wire:loading.attr="disabled"
                        class="group inline-flex items-center justify-center text-sm font-bold text-[#1F6F5F] hover:text-[#2FA084] disabled:text-gray-400 transition-all cursor-pointer"
                    >
                        <x-icon name="o-arrow-path" class="w-4 h-4 mr-2 group-hover:rotate-180 transition-transform duration-500" wire:loading.class="animate-spin" wire:target="resendCode" />
                        <span wire:loading.remove wire:target="resendCode">
                            <span x-show="timer === 0">Didn't receive it? Resend code</span>
                            <span x-show="timer > 0" x-text="'Resend code in ' + timer + 's'" x-cloak></span>
                        </span>
                        <span wire:loading wire:target="resendCode">Sending new code...</span>
                    </button>
                </div>

                <div class="pt-4 text-center border-t border-gray-100 dark:border-gray-800">
                    <a href="{{ route('user.login') }}" wire:navigate class="text-xs font-bold text-gray-500 hover:text-[#1F6F5F] transition-colors">
                        Back to Sign In
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
