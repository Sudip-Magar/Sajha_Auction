<div class="min-h-screen flex items-center justify-center bg-gray-50 px-4">
    <div class="bg-white rounded-2xl shadow-lg p-8 w-full max-w-md text-center">

        {{-- Avatar if available --}}
        <div class="w-16 h-16 rounded-full bg-blue-100 flex items-center justify-center mx-auto mb-4 text-2xl">
            ✉️
        </div>

        <h1 class="text-2xl font-bold text-gray-800 mb-1">Verify your email</h1>
        <p class="text-gray-500 text-sm mb-6">
            We sent a 6-digit code to <strong>{{ $email }}</strong>
        </p>

        @if($message)
            <div class="mb-4 p-3 rounded-lg text-sm {{ $isError ? 'bg-red-50 text-red-600 border border-red-200' : 'bg-green-50 text-green-600 border border-green-200' }}">
                {{ $message }}
            </div>
        @endif

        <input
            wire:model="otp"
            type="text"
            inputmode="numeric"
            maxlength="6"
            placeholder="· · · · · ·"
            class="w-full text-center text-4xl tracking-[1rem] border-2 border-gray-200 rounded-xl py-4 focus:border-blue-500 focus:outline-none font-mono mb-4"
            autofocus
        />

        <button
            wire:click="verifyOtp"
            wire:loading.attr="disabled"
            class="w-full bg-blue-600 hover:bg-blue-700 disabled:opacity-60 text-white font-semibold py-3 rounded-xl transition mb-3"
        >
            <span wire:loading.remove wire:target="verifyOtp">Verify Code</span>
            <span wire:loading wire:target="verifyOtp">Verifying...</span>
        </button>

        <button
            wire:click="sendOtp"
            wire:loading.attr="disabled"
            class="text-sm text-blue-600 hover:underline disabled:opacity-40"
        >
            <span wire:loading.remove wire:target="sendOtp">Resend OTP</span>
            <span wire:loading wire:target="sendOtp">Sending...</span>
        </button>
    </div>
</div>
