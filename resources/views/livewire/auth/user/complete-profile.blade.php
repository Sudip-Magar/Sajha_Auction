<div x-data="{ agree: @entangle('agree_terms').live }"
     class="min-h-[calc(100vh-64px)] bg-linear-to-br from-[#1F6F5F] to-[#2FA084] py-8 px-4 flex items-center justify-center">
    <div
        class="w-full max-w-5xl bg-white/95 backdrop-blur-xl rounded-[2rem] shadow-2xl overflow-hidden border border-white/20">

        {{-- Main 2-column layout --}}
        <div class="flex flex-col lg:flex-row">

            {{-- LEFT COLUMN: Summary + Avatar --}}
            <div
                class="w-full lg:w-[320px] bg-linear-to-br from-[#1F6F5F] to-[#2FA084] p-8 text-white flex flex-col justify-center relative overflow-hidden">
                <div class="absolute top-0 right-0 w-32 h-32 bg-white/10 rounded-full -mr-16 -mt-16 blur-2xl"></div>

                <div class="relative z-10 text-center lg:text-left mb-8">
                    <h1 class="text-3xl font-black tracking-tight leading-tight">Complete Profile</h1>
                    <p class="text-white/70 text-sm mt-2 font-medium">Almost there! We just need a few more details.</p>
                </div>

                {{-- Avatar Upload Card --}}
                <div
                    class="relative z-10 bg-white/10 backdrop-blur-md border border-white/20 rounded-2xl p-6 text-center shadow-xl">
                    <div class="relative inline-block mb-4 group">
                        @if ($avatarFile)
                            <img src="{{ $avatarFile->temporaryUrl() }}"
                                 class="w-24 h-24 rounded-full object-cover ring-4 ring-white/30 mx-auto transition group-hover:scale-105"
                                 alt="Avatar"/>
                        @elseif ($googleAvatar)
                            <img src="{{ $googleAvatar }}"
                                 class="w-24 h-24 rounded-full object-cover ring-4 ring-white/30 mx-auto transition group-hover:scale-105"
                                 alt="Avatar"/>
                        @else
                            <img src="{{ asset('assets/images/user.png') }}"
                                 class="w-24 h-24 rounded-full object-cover ring-4 ring-white/30 mx-auto transition group-hover:scale-105"
                                 alt="Avatar"/>
                        @endif
                        <label for="avatarUpload"
                               class="absolute bottom-1 right-1 w-8 h-8 bg-white text-[#1F6F5F] rounded-full flex items-center justify-center shadow-lg cursor-pointer hover:scale-110 transition active:scale-95">
                            <x-icon name="o-camera" class="w-4 h-4"/>
                        </label>
                        <input type="file" accept="image/*" wire:model.live="avatarFile" class="hidden"
                               id="avatarUpload">
                    </div>

                    <h3 class="font-bold text-white text-lg truncate">{{ $name ?: 'New User' }}</h3>
                    <p class="text-white/60 text-xs truncate mb-4">{{ $email }}</p>

                    <div
                        class="flex items-center justify-center gap-2 text-[10px] font-black tracking-widest uppercase bg-white/10 py-2 rounded-lg border border-white/10">
                        <x-icon name="o-check-badge" class="w-3 h-3 text-green-300"/>
                        Identity Verified
                    </div>
                </div>
            </div>

            {{-- RIGHT COLUMN: The Form --}}
            <div class="flex-1 p-8 lg:p-10">
                <div class="space-y-5">
                    {{-- Row 1: Identity --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <x-input label="Full Name" wire:model="name" icon="o-user-circle" placeholder="Your full name"
                                 class="bg-gray-50/50 border-gray-200"/>
                        <x-input label="Username" wire:model.live.debounce.500ms="username" icon="o-at-symbol"
                                 placeholder="username" class="bg-gray-50/50 border-gray-200"/>
                    </div>

                    {{-- Row 2: Contact & Demographics --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <x-input label="Phone Number" wire:model="phone" type="number" icon="o-phone"
                                 placeholder="98XXXXXXXX" class="bg-gray-50/50 border-gray-200 no-spinner"/>
                        <div>
                            <label for="date_of_birth_np" class="fieldset-legend mb-0.5">Date of Birth (B.S.)</label>
                            <input
                                id="date_of_birth_np"
                                wire:model="date_of_birth_np"
                                data-nepali-date="date_of_birth"
                                autocomplete="off"
                                class="input w-full bg-gray-50/50 border-gray-200"
                                placeholder="YYYY-MM-DD"
                            >
                            <input
                                type="hidden"
                                wire:model="date_of_birth_en"
                                data-english-date="date_of_birth"
                            >
                            @error('date_of_birth_en')
                                <small class="text-red-500">{{ $message }}</small>
                            @enderror
                        </div>
                    </div>

                    {{-- Row 3: Bio & Gender --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <x-select label="Gender" icon="o-user" :options="$genderStates" wire:model="gender"
                                  placeholder="Select Gender" class="bg-gray-50 border-gray-200"/>
                        <x-textarea label="Short Bio" wire:model="bio" rows="2" placeholder="Tell us about yourself..."
                                    class="bg-gray-50/50 border-gray-200 resize-none"/>
                    </div>

                    {{-- Apply as Seller Toggle --}}
                    <div class="bg-gray-50 border border-gray-100 rounded-2xl p-4 flex items-center justify-between">
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 bg-[#2FA084]/10 rounded-xl flex items-center justify-center">
                                <x-icon name="o-shopping-bag" class="w-5 h-5 text-[#2FA084]"/>
                            </div>
                            <div>
                                <h4 class="text-xs font-bold text-gray-800">Apply as Seller</h4>
                                <p class="text-[10px] text-gray-500 font-medium">Request seller access to list products for sale or auction</p>
                            </div>
                        </div>
                        <x-toggle wire:model="apply_as_seller" class="toggle-primary"/>
                    </div>

                    {{-- Row 4: Password Security --}}
                    <div x-data="{
                        pw: @entangle('password').live,
                        cpw: @entangle('confirm_password').live,
                        get passwordsMatch() { return this.cpw && this.cpw.length > 0 && this.pw === this.cpw },
                        rules: [
                            { check: () => /[A-Z]/.test($data.pw), text: 'A-Z' },
                            { check: () => /[a-z]/.test($data.pw), text: 'a-z' },
                            { check: () => /[0-9]/.test($data.pw), text: '0-9' },
                            { check: () => $data.pw.length >= 8, text: '8+ chars' }
                        ]
                    }" class="space-y-3">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <x-password label="Password" wire:model.live="password" placeholder="New Password"
                                        class="bg-gray-50/50 border-gray-200"/>
                            <x-password label="Confirm Password" wire:model.live="confirm_password"
                                        placeholder="Repeat Password" class="bg-gray-50/50 border-gray-200"/>
                        </div>

                        {{-- Compact Password Feedback --}}
                        <div class="flex flex-wrap items-center gap-3">
                            <template x-for="rule in rules">
                                <div class="flex items-center gap-1.5 transition-all duration-200"
                                     :class="rule.check() ? 'text-green-600' : 'text-gray-400'">
                                    <div class="w-3.5 h-3.5 rounded-full flex items-center justify-center border"
                                         :class="rule.check() ? 'bg-green-500 border-green-500 text-white' : 'bg-white border-gray-200 text-gray-200'">
                                        <x-icon name="o-check" class="w-2.5 h-2.5"/>
                                    </div>
                                    <span class="text-[10px] font-bold" x-text="rule.text"></span>
                                </div>
                            </template>
                            <div x-show="cpw && cpw.length > 0" class="flex items-center gap-1.5 ml-auto">
                                <span class="text-[10px] font-bold"
                                      :class="passwordsMatch ? 'text-green-600' : 'text-red-500'"
                                      x-text="passwordsMatch ? 'Passwords Match' : 'Mismatch'"></span>
                            </div>
                        </div>
                    </div>

                    {{-- Terms Agreement --}}
                    <div class="bg-gray-50 border border-gray-100 rounded-2xl p-4">
                        <label class="flex items-start gap-3 cursor-pointer">
                            <input type="checkbox" wire:model.live="agree_terms" class="checkbox checkbox-primary mt-0.5"/>
                            <span class="text-xs font-semibold text-gray-700">
                                I agree to the
                                <a href="{{ asset('assets/documents/terms-and-conditions.pdf') }}"
                                   target="_blank" rel="noopener" @click.stop
                                   class="font-bold text-[#1F6F5F] underline hover:text-[#2FA084]">Terms of Use and Privacy Policy</a>.
                            </span>
                        </label>
                        @error('agree_terms')
                            <small class="text-red-500">{{ $message }}</small>
                        @enderror
                    </div>

                    {{-- Action Button --}}
                    <div class="pt-2">
                        <x-button
                            label="Finalize Account"
                            wire:click="register"
                            spinner="register"
                            x-bind:disabled="!agree"
                            class="w-full bg-linear-to-r from-[#1F6F5F] to-[#2FA084] hover:shadow-lg hover:shadow-[#2FA084]/30 text-white border-none h-14 rounded-2xl font-black text-lg transition-all transform hover:-translate-y-0.5"
                            x-bind:class="!agree ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer'"
                        />
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
