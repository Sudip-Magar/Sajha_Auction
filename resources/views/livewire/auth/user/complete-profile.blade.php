<div x-data class="min-h-screen bg-linear-to-br from-[#1F6F5F] to-[#2FA084] py-6 px-4 flex items-center justify-center">
    <div class="w-full max-w-5xl bg-white/95 backdrop-blur-xl rounded-3xl shadow-2xl overflow-hidden transition-all duration-300">

        {{-- Header --}}
        <div class="bg-linear-to-r from-[#1F6F5F] to-[#2FA084] px-6 py-5 sm:px-10 sm:py-6 text-white relative overflow-hidden">
            <div class="absolute inset-0 bg-black/10"></div>
            <div class="relative z-10">
                <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight">Complete your profile</h1>
                <p class="text-gray-100 text-xs sm:text-sm mt-1 opacity-90">You're almost there! Let's get to know you better.</p>
            </div>
            <div class="absolute -top-10 -right-10 w-32 h-32 bg-white/10 rounded-full blur-2xl"></div>
            <div class="absolute -bottom-10 -left-10 w-32 h-32 bg-black/10 rounded-full blur-xl"></div>
        </div>

        {{-- Main 2-column layout --}}
        <div class="p-5 sm:p-8">
            <div class="flex flex-col lg:flex-row gap-6 lg:gap-8">

                {{-- LEFT COLUMN: Avatar + Bio --}}
                <div class="w-full lg:w-[280px] flex-shrink-0 space-y-5">
                    {{-- Avatar Card --}}
                    <div class="bg-white border border-gray-100 shadow-sm rounded-2xl p-5 text-center transition hover:shadow-md">
                        <div class="relative inline-block mb-3">
                            @if ($avatarFile)
                                <img src="{{ $avatarFile->temporaryUrl() }}"
                                    class="w-24 h-24 rounded-full object-cover ring-4 ring-[#2FA084]/20 mx-auto" alt="Avatar" />
                            @elseif ($googleAvatar)
                                <img src="{{ $googleAvatar }}"
                                    class="w-24 h-24 rounded-full object-cover ring-4 ring-[#2FA084]/20 mx-auto" alt="Avatar" />
                            @else
                                <img src="{{ asset('assets/images/user.png') }}"
                                    class="w-24 h-24 rounded-full object-cover ring-4 ring-[#2FA084]/20 mx-auto" alt="Avatar" />
                            @endif
                            <div class="absolute bottom-1 right-1 w-4 h-4 bg-green-500 border-2 border-white rounded-full"></div>
                        </div>
                        <p class="font-bold text-gray-800 text-base">{{ $name }}</p>
                        <p class="text-xs text-gray-500 font-medium">{{ $email }}</p>
                        <span class="inline-flex items-center gap-1.5 text-xs font-bold text-[#1F6F5F] bg-[#2FA084]/10 px-3 py-1 rounded-full mt-2">
                            <x-icon name="o-check-badge" class="w-3.5 h-3.5" /> Verified
                        </span>
                        <div class="mt-4">
                            <input type="file" accept="image/*" wire:model.live="avatarFile" class="hidden" id="avatarUpload">
                            <label for="avatarUpload"
                                class="inline-flex items-center justify-center gap-2 w-full px-4 py-2 bg-linear-to-r from-[#1F6F5F] to-[#2FA084] text-white text-xs font-semibold rounded-xl shadow-md hover:scale-105 cursor-pointer transition-transform duration-150">
                                <x-icon name="o-camera" class="w-3.5 h-3.5" />
                                <span>Upload Image</span>
                            </label>
                        </div>
                    </div>

                    {{-- Bio (tucked into left column) --}}
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">Bio</label>
                        <textarea wire:model="bio" rows="4" placeholder="Tell us a little about yourself..."
                            class="w-full bg-gray-50/50 border border-gray-200 rounded-xl px-4 py-2.5 focus:outline-none focus:border-[#2FA084] focus:ring-2 focus:ring-[#2FA084]/20 transition-all font-medium text-gray-800 text-sm resize-none"></textarea>
                    </div>
                </div>

                {{-- RIGHT COLUMN: All form fields --}}
                <div class="flex-1 min-w-0">

                    <div class="space-y-4">
                        {{-- Row 1: Name + Username --}}
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <div class="flex justify-between mb-1">
                                    <label class="text-sm font-semibold text-gray-700">Full Name <span class="text-red-500">*</span></label>
                                    @error('name') <span class="text-xs text-red-500 font-medium">{{ $message }}</span> @enderror
                                </div>
                                <input wire:model="name" type="text"
                                    class="w-full bg-gray-50/50 border border-gray-200 rounded-xl px-4 py-2.5 focus:outline-none focus:border-[#2FA084] focus:ring-2 focus:ring-[#2FA084]/20 transition-all font-medium text-gray-800 text-sm" />
                            </div>
                            <div>
                                <div class="flex justify-between mb-1">
                                    <label class="text-sm font-semibold text-gray-700">Username <span class="text-red-500">*</span></label>
                                    @error('username') <span class="text-xs text-red-500 font-medium">{{ $message }}</span> @enderror
                                </div>
                                <div class="relative">
                                    <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-[#2FA084] font-bold text-sm">@</span>
                                    <input wire:model.live.debounce.500ms="username" type="text"
                                        class="w-full bg-gray-50/50 border border-gray-200 rounded-xl pl-8 pr-4 py-2.5 focus:outline-none focus:border-[#2FA084] focus:ring-2 focus:ring-[#2FA084]/20 transition-all font-medium text-gray-800 text-sm" />
                                </div>
                            </div>
                        </div>

                        {{-- Row 2: Phone + DOB --}}
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <div class="flex justify-between mb-1">
                                    <label class="text-sm font-semibold text-gray-700">Phone Number <span class="text-red-500">*</span></label>
                                    @error('phone') <span class="text-xs text-red-500 font-medium">{{ $message }}</span> @enderror
                                </div>
                                <input wire:model="phone" type="number" placeholder="98XXXXXXXX"
                                    class="w-full no-spinner bg-gray-50/50 border border-gray-200 rounded-xl px-4 py-2.5 focus:outline-none focus:border-[#2FA084] focus:ring-2 focus:ring-[#2FA084]/20 transition-all font-medium text-gray-800 text-sm" />
                            </div>
                            <div>
                                <div class="flex justify-between mb-1">
                                    <label class="text-sm font-semibold text-gray-700">Date of Birth <span class="text-red-500">*</span></label>
                                    @error('date_of_birth') <span class="text-xs text-red-500 font-medium">{{ $message }}</span> @enderror
                                </div>
                                <input wire:model="date_of_birth" type="date"
                                    class="w-full bg-gray-50/50 border border-gray-200 rounded-xl px-4 py-2.5 focus:outline-none focus:border-[#2FA084] focus:ring-2 focus:ring-[#2FA084]/20 transition-all font-medium text-gray-800 text-sm" />
                            </div>
                        </div>

                        {{-- Row 3: Gender (full width) --}}
                        <div>
                            <div class="flex justify-between mb-1">
                                <label class="text-sm font-semibold text-gray-700">Gender <span class="text-red-500">*</span></label>
                                @error('gender') <span class="text-xs text-red-500 font-medium">{{ $message }}</span> @enderror
                            </div>
                            <select wire:model="gender"
                                class="w-full bg-gray-50/50 border border-gray-200 rounded-xl px-4 py-2.5 focus:outline-none focus:border-[#2FA084] focus:ring-2 focus:ring-[#2FA084]/20 transition-all font-medium text-gray-800 text-sm appearance-none">
                                <option value="">Select...</option>
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                                <option value="non_binary">Non-binary</option>
                                <option value="prefer_not_to_say">Prefer not to say</option>
                            </select>
                        </div>

                        {{-- Row 4: Password + Confirm Password --}}
                        <div x-data="{
                            pw: '',
                            cpw: '',
                            get hasUpper() { return /[A-Z]/.test(this.pw) },
                            get hasLower() { return /[a-z]/.test(this.pw) },
                            get hasNumber() { return /[0-9]/.test(this.pw) },
                            get hasSpecial() { return /[@$!%*#?&]/.test(this.pw) },
                            get hasMinLen() { return this.pw.length >= 8 },
                            get noSpaces() { return this.pw.length > 0 && !/\s/.test(this.pw) },
                            get notEmail() { return this.pw.length > 0 && this.pw !== $wire.email },
                            get notUsername() { return this.pw.length > 0 && this.pw !== $wire.username },
                            get passwordsMatch() { return this.cpw.length > 0 && this.pw === this.cpw },
                        }" class="space-y-3">

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                {{-- Password --}}
                                <div>
                                    <div class="flex justify-between mb-1">
                                        <label class="text-sm font-semibold text-gray-700">Password <span class="text-red-500">*</span></label>
                                        @error('password') <span class="text-xs text-red-500 font-medium">{{ $message }}</span> @enderror
                                    </div>
                                    <div class="relative">
                                        <input wire:model="password" x-model="pw"
                                            :type="$store.profileSetup.isVisible ? 'text' : 'password'"
                                            class="w-full bg-gray-50/50 border border-gray-200 rounded-xl px-4 py-2.5 pr-10 focus:outline-none focus:border-[#2FA084] focus:ring-2 focus:ring-[#2FA084]/20 transition-all font-medium text-gray-800 text-sm" />
                                        <button type="button" @click="$store.profileSetup.toggleVisible()"
                                            class="cursor-pointer absolute right-3 top-1/2 -translate-y-1/2 duration-150">
                                            <template x-if="$store.profileSetup.isVisible">
                                                <x-icon name="o-eye" class="w-4 h-4 text-gray-500" />
                                            </template>
                                            <template x-if="!$store.profileSetup.isVisible">
                                                <x-icon name="o-eye-slash" class="w-4 h-4 text-gray-500" />
                                            </template>
                                        </button>
                                    </div>
                                </div>

                                {{-- Confirm Password --}}
                                <div>
                                    <div class="flex justify-between mb-1">
                                        <label class="text-sm font-semibold text-gray-700">Confirm Password <span class="text-red-500">*</span></label>
                                        @error('confirm_password') <span class="text-xs text-red-500 font-medium">{{ $message }}</span> @enderror
                                    </div>
                                    <div class="relative">
                                        <input wire:model="confirm_password" x-model="cpw"
                                            :type="$store.profileSetup.isConfirmPasswordVisible ? 'text' : 'password'"
                                            class="w-full bg-gray-50/50 border border-gray-200 rounded-xl px-4 py-2.5 pr-10 focus:outline-none transition-all font-medium text-gray-800 text-sm"
                                            :class="cpw.length > 0 ? (passwordsMatch ? 'border-[#2FA084] focus:border-[#2FA084] focus:ring-2 focus:ring-[#2FA084]/20' : 'border-red-400 focus:border-red-400 focus:ring-2 focus:ring-red-400/20') : 'border-gray-200 focus:border-[#2FA084] focus:ring-2 focus:ring-[#2FA084]/20'" />
                                        <button type="button" @click="$store.profileSetup.toggleConfirmPasswordVisible()"
                                            class="cursor-pointer absolute right-3 top-1/2 -translate-y-1/2 duration-150">
                                            <template x-if="$store.profileSetup.isConfirmPasswordVisible">
                                                <x-icon name="o-eye" class="w-4 h-4 text-gray-500" />
                                            </template>
                                            <template x-if="!$store.profileSetup.isConfirmPasswordVisible">
                                                <x-icon name="o-eye-slash" class="w-4 h-4 text-gray-500" />
                                            </template>
                                        </button>
                                    </div>
                                    {{-- Match indicator --}}
                                    <div x-show="cpw.length > 0" x-transition class="mt-1">
                                        <span x-show="passwordsMatch" class="text-xs font-semibold text-[#1F6F5F] flex items-center gap-1">
                                            <span class="w-3 h-3 rounded-full bg-[#2FA084] text-white flex items-center justify-center text-[8px]">✓</span> Passwords match
                                        </span>
                                        <span x-show="!passwordsMatch" class="text-xs font-semibold text-red-500 flex items-center gap-1">
                                            <span class="w-3 h-3 rounded-full bg-red-400 text-white flex items-center justify-center text-[8px]">✗</span> Passwords do not match
                                        </span>
                                    </div>
                                </div>
                            </div>

                            {{-- Password rules checklist --}}
                            <div x-show="pw.length > 0" x-transition
                                class="grid grid-cols-2 sm:grid-cols-4 gap-x-4 gap-y-1.5 bg-gray-50/80 rounded-xl p-3 border border-gray-100">
                                <template
                                    x-for="rule in [
                                        { check: hasMinLen, text: 'Min 8 chars' },
                                        { check: hasUpper, text: 'Uppercase (A-Z)' },
                                        { check: hasLower, text: 'Lowercase (a-z)' },
                                        { check: hasNumber, text: 'Number (0-9)' },
                                        { check: hasSpecial, text: 'Special (@$!%*)' },
                                        { check: noSpaces, text: 'No spaces' },
                                        { check: notEmail, text: '≠ Email' },
                                        { check: notUsername, text: '≠ Username' },
                                    ]"
                                    :key="rule.text">
                                    <div class="flex items-center gap-1.5 text-[11px] font-medium transition-colors duration-200"
                                        :class="rule.check ? 'text-[#1F6F5F]' : 'text-gray-400'">
                                        <span class="flex-shrink-0 w-3.5 h-3.5 rounded-full flex items-center justify-center text-[9px] font-bold border transition-all duration-200"
                                            :class="rule.check ? 'bg-[#2FA084] text-white border-[#2FA084]' : 'bg-white border-gray-300'">
                                            <span x-show="rule.check">✓</span>
                                            <span x-show="!rule.check">·</span>
                                        </span>
                                        <span x-text="rule.text"></span>
                                    </div>
                                </template>
                            </div>
                        </div>

                        {{-- Submit button --}}
                        <div class="pt-2">
                            <button wire:click="register" wire:loading.attr="disabled"
                                class="w-full bg-linear-to-r from-[#1F6F5F] to-[#2FA084] hover:shadow-lg hover:shadow-[#2FA084]/30 disabled:opacity-70 cursor-pointer text-white font-bold py-3.5 rounded-xl transition-all duration-300 transform hover:-translate-y-0.5">
                                <span wire:loading.remove wire:target="register">Create Account</span>
                                <span wire:loading wire:target="register" class="flex items-center justify-center gap-2">
                                    <x-icon name="o-arrow-path" class="w-5 h-5 animate-spin" /> Creating your account...
                                </span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@script
    <script>
        Alpine.store('profileSetup', {
            isVisible: false,
            isConfirmPasswordVisible: false,

            toggleVisible() {
                this.isVisible = !this.isVisible;
            },

            toggleConfirmPasswordVisible() {
                this.isConfirmPasswordVisible = !this.isConfirmPasswordVisible;
            }
        })
    </script>
@endscript
