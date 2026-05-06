<div x-data="{ timer: {{ $countdown }} }" 
     x-init="setInterval(() => { if(timer > 0) timer-- }, 1000)" 
     @start-countdown.window="timer = 30"
     class="min-h-screen flex items-center justify-center bg-gradient-to-br from-[#1F6F5F] to-[#2FA084] px-4">
    
    <div class="bg-white/95 backdrop-blur-xl rounded-3xl shadow-2xl p-10 w-full max-w-md text-center transform transition-all hover:scale-[1.01]">

        {{-- Avatar/Icon --}}
        <div class="w-20 h-20 rounded-full bg-gradient-to-br from-[#1F6F5F]/10 to-[#2FA084]/20 flex items-center justify-center mx-auto mb-6 shadow-inner relative">
            <x-icon name="o-envelope-open" class="w-10 h-10 text-[#1F6F5F]" />
            <div class="absolute top-0 right-0 w-5 h-5 bg-[#2FA084] rounded-full border-2 border-white animate-pulse"></div>
        </div>

        <h1 class="text-3xl font-extrabold text-gray-800 mb-2 tracking-tight">Verify your email</h1>
        <p class="text-gray-500 font-medium mb-8">
            We sent a 6-digit code to <br><strong class="text-[#1F6F5F]">{{ $email }}</strong>
        </p>

        @if($message)
            <div class="mb-6 p-4 rounded-xl text-sm font-semibold {{ $isError ? 'bg-red-50 text-red-600 border border-red-100' : 'bg-green-50 text-green-700 border border-green-100' }}">
                {{ $message }}
            </div>
        @endif

        <div class="mt-4 mb-8 flex items-center justify-center">
            <div class="p-2 bg-gray-50 rounded-2xl border border-gray-100 shadow-inner">
                <x-pin wire:model.live="otp" size="6" numeric class="focus:border-[#2FA084]" />
            </div>
        </div>

        <button
            wire:click="verifyOtp"
            wire:loading.attr="disabled"
            wire:target="verifyOtp"
            class="w-full bg-gradient-to-r from-[#1F6F5F] to-[#2FA084] hover:shadow-lg hover:shadow-[#2FA084]/30 disabled:opacity-70 text-white font-bold py-4 rounded-xl transition-all duration-300 transform hover:-translate-y-0.5 mb-4"
        >
            <span wire:loading.remove wire:target="verifyOtp">Verify Code</span>
            <span wire:loading wire:target="verifyOtp" class="flex items-center justify-center gap-2">
                <x-icon name="o-arrow-path" class="w-5 h-5 animate-spin" /> Verifying...
            </span>
        </button>

        <button
            wire:click="sendOtp"
            x-bind:disabled="timer > 0"
            wire:loading.attr="disabled"
            class="text-sm font-bold text-[#1F6F5F] hover:text-[#2FA084] disabled:text-gray-400 transition-colors"
        >
            <span wire:loading.remove wire:target="sendOtp">
                <span x-show="timer === 0">Didn't receive it? Resend OTP</span>
                <span x-show="timer > 0" x-text="'Resend code in ' + timer + 's'" x-cloak></span>
            </span>
            <span wire:loading wire:target="sendOtp">Sending new code...</span>
        </button>
    </div>
</div>
