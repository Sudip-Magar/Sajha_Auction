<div class="min-h-screen bg-gray-50 py-12 px-4">
    <div class="max-w-2xl mx-auto bg-white rounded-2xl shadow-lg overflow-hidden">

        {{-- Header --}}
        <div class="bg-gradient-to-r from-blue-600 to-indigo-600 px-8 py-6 text-white">
            <h1 class="text-2xl font-bold">Complete your profile</h1>
            <p class="text-blue-100 text-sm mt-1">You're almost there! Fill in a few more details.</p>
        </div>

        <div class="p-8">
            {{-- Google info card (pre-filled, read-only) --}}
            <div class="flex items-center gap-4 bg-gray-50 border border-gray-200 rounded-xl p-4 mb-8">
                <img src="{{ $avatar }}" class="w-16 h-16 rounded-full object-cover ring-2 ring-blue-200" alt="Avatar" />
                <div>
                    <p class="font-semibold text-gray-800">{{ $name }}</p>
                    <p class="text-sm text-gray-500">{{ $email }}</p>
                    <span class="inline-flex items-center gap-1 text-xs text-green-600 bg-green-50 px-2 py-0.5 rounded-full mt-1">
                        ✓ Verified via Google
                    </span>
                </div>
            </div>

            {{-- Validation errors --}}
            @if($errors->any())
                <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-xl">
                    <ul class="text-sm text-red-600 space-y-1 list-disc list-inside">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="space-y-5">
                {{-- Name --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Full Name <span class="text-red-500">*</span></label>
                    <input wire:model="name" type="text"
                        class="w-full border border-gray-200 rounded-xl px-4 py-2.5 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500" />
                </div>

                {{-- Username --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Username <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">@</span>
                        <input wire:model.live.debounce.500ms="username" type="text"
                            class="w-full border border-gray-200 rounded-xl pl-8 pr-4 py-2.5 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500" />
                    </div>
                </div>

                {{-- Phone --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
                    <input wire:model="phone" type="tel"
                        placeholder="+977 98XXXXXXXX"
                        class="w-full border border-gray-200 rounded-xl px-4 py-2.5 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500" />
                </div>

                {{-- DOB + Gender side by side --}}
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Date of Birth</label>
                        <input wire:model="date_of_birth" type="date"
                            class="w-full border border-gray-200 rounded-xl px-4 py-2.5 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Gender</label>
                        <select wire:model="gender"
                            class="w-full border border-gray-200 rounded-xl px-4 py-2.5 focus:outline-none focus:border-blue-500 bg-white">
                            <option value="">Select...</option>
                            <option value="male">Male</option>
                            <option value="female">Female</option>
                            <option value="non_binary">Non-binary</option>
                            <option value="prefer_not_to_say">Prefer not to say</option>
                        </select>
                    </div>
                </div>

                {{-- Bio --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Bio</label>
                    <textarea wire:model="bio" rows="3"
                        placeholder="Tell us a little about yourself..."
                        class="w-full border border-gray-200 rounded-xl px-4 py-2.5 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 resize-none"></textarea>
                </div>

                {{-- Submit --}}
                <button
                    wire:click="register"
                    wire:loading.attr="disabled"
                    class="w-full bg-blue-600 hover:bg-blue-700 disabled:opacity-60 text-white font-semibold py-3 rounded-xl transition-colors mt-2"
                >
                    <span wire:loading.remove wire:target="register">Create Account</span>
                    <span wire:loading wire:target="register">Creating your account...</span>
                </button>
            </div>
        </div>
    </div>
</div>
