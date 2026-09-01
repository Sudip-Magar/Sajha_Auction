<div x-data="{ timer: {{ $countdown }} }" 
     x-init="setInterval(() => { if(timer > 0) timer-- }, 1000)" 
     @start-countdown.window="timer = 30"
     class="min-h-[calc(100vh-64px)] flex items-center justify-center bg-linear-to-br from-[#1F6F5F] to-[#2FA084] px-4 py-8">
    
    <div class="w-full max-w-4xl grid lg:grid-cols-2 bg-white/10 backdrop-blur-2xl rounded-[2.5rem] shadow-2xl border border-white/20 overflow-hidden">
        {{-- Left side: Branding/Icon (Compact) --}}
        <div class="hidden lg:flex flex-col justify-center p-12 text-white bg-linear-to-br from-white/10 to-white/5 relative overflow-hidden">
            <div class="absolute top-0 right-0 w-32 h-32 bg-white/10 rounded-full -mr-16 -mt-16 blur-2xl"></div>
            <div class="absolute bottom-0 left-0 w-24 h-24 bg-[#2FA084]/20 rounded-full -ml-12 -mb-12 blur-xl"></div>
            
            <div class="w-20 h-20 rounded-2xl bg-white/10 flex items-center justify-center mb-6 border border-white/20 shadow-inner z-10">
                <x-icon name="o-envelope-open" class="w-10 h-10 text-white" />
            </div>
            
            <h2 class="text-4xl font-black tracking-tight mb-4 leading-tight z-10">Check Your<br/>Inbox.</h2>
            <p class="text-white/70 text-sm font-medium leading-relaxed max-w-xs z-10">We've sent a security code to your email. Please enter it to verify your account.</p>
        </div>

        {{-- Right side: Form (Compact) --}}
        <div class="p-8 lg:p-12 bg-white/95 text-center flex flex-col justify-center">
            <div class="mb-8">
                <h1 class="text-3xl font-black text-gray-900 tracking-tight">Email Verification</h1>
                <p class="text-gray-500 text-sm mt-2 font-medium">Code sent to <br><strong class="text-[#1F6F5F]">{{ $email }}</strong></p>
            </div>

            @if($message)
                <div class="mb-6 p-4 rounded-xl text-xs font-bold {{ $isError ? 'bg-red-50 text-red-600 border border-red-100' : 'bg-green-50 text-green-700 border border-green-100' }}">
                    <x-icon name="{{ $isError ? 'o-x-circle' : 'o-check-circle' }}" class="w-4 h-4 inline mr-1" />
                    {{ $message }}
                </div>
            @endif

            <div class="mb-8 flex items-center justify-center">
                <div class="p-4 bg-gray-50 rounded-3xl border border-gray-100 shadow-inner">
                    <x-pin wire:model.live="otp" size="6" numeric class="focus:border-[#2FA084] gap-2" />
                </div>
            </div>

            <x-button 
                label="Verify Code" 
                wire:click="verifyOtp" 
                spinner="verifyOtp" 
                class="w-full bg-linear-to-r from-[#1F6F5F] to-[#2FA084] hover:shadow-lg hover:shadow-[#2FA084]/30 text-white border-none h-14 rounded-2xl font-black text-lg transition-all transform hover:-translate-y-0.5" 
            />

            <div class="mt-6 pt-6 border-t border-gray-100">
                <button
                    wire:click="sendOtp"
                    x-bind:disabled="timer > 0"
                    wire:loading.attr="disabled"
                    class="group flex items-center justify-center mx-auto text-sm font-bold text-[#1F6F5F] hover:text-[#2FA084] disabled:text-gray-400 transition-all cursor-pointer"
                >
                    <x-icon name="o-arrow-path" class="w-4 h-4 mr-2 group-hover:rotate-180 transition-transform duration-500" wire:loading.class="animate-spin" />
                    <span wire:loading.remove wire:target="sendOtp">
                        <span x-show="timer === 0">Didn't receive it? Resend OTP</span>
                        <span x-show="timer > 0" x-text="'Resend code in ' + timer + 's'" x-cloak></span>
                    </span>
                    <span wire:loading wire:target="sendOtp">Sending new code...</span>
                </button>
            </div>
        </div>
    </div>
</div>
