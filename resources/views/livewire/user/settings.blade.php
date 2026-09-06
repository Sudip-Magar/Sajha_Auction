<div class="max-w-5xl mx-auto py-10 px-4 sm:px-6 lg:px-8">
    <x-header title="Account Settings" subtitle="Manage your profile and security preferences" separator />

    <div class="flex flex-col md:flex-row gap-8 mt-6">
        <!-- Sidebar Navigation -->
        <div class="w-full md:w-64 space-y-2">
            <x-button label="Public Profile" icon="o-user" 
                class="w-full justify-start {{ $activeTab === 'profile' ? 'btn-primary' : 'btn-ghost' }}" 
                wire:click="$set('activeTab', 'profile')" />
            <x-button label="Password & Security" icon="o-shield-check" 
                class="w-full justify-start {{ $activeTab === 'security' ? 'btn-primary' : 'btn-ghost' }}" 
                wire:click="$set('activeTab', 'security')" />
            <x-button label="Notifications" icon="o-bell" 
                class="w-full justify-start {{ $activeTab === 'notifications' ? 'btn-primary' : 'btn-ghost' }}" 
                wire:click="$set('activeTab', 'notifications')" />
        </div>

        <!-- Main Content Area -->
        <div class="flex-1 bg-white rounded-3xl shadow-xl shadow-gray-200/50 border border-gray-100 overflow-hidden dark:bg-[#181A1F] dark:border-gray-800 dark:shadow-none">

            @if($activeTab === 'profile')
                <div class="p-8">
                    <h3 class="text-xl font-black text-gray-900 mb-6 dark:text-gray-100">Profile Information</h3>
                    
                    <x-form wire:submit="updateProfile" class="space-y-8">
                        <div class="flex flex-col md:flex-row gap-8 items-start">
                            <div class="relative group">
                                <div class="w-32 h-32 rounded-3xl overflow-hidden border-4 border-gray-50 shadow-inner group-hover:opacity-75 transition-opacity bg-gray-100 flex items-center justify-center dark:border-gray-800 dark:bg-gray-800">
                                    @if($avatar)
                                        <img src="{{ $avatar->temporaryUrl() }}" class="w-full h-full object-cover">
                                    @elseif(auth()->user()->avatar)
                                        <img src="{{ Storage::url(auth()->user()->avatar) }}" class="w-full h-full object-cover">
                                    @else
                                        <x-icon name="o-user" class="w-12 h-12 text-gray-300 dark:text-gray-600" />
                                    @endif
                                </div>
                                <x-file wire:model="avatar" accept="image/*" class="hidden" id="avatar-input" />
                                <label for="avatar-input" class="absolute -bottom-2 -right-2 w-10 h-10 bg-white rounded-xl shadow-lg border border-gray-100 flex items-center justify-center cursor-pointer hover:scale-110 transition-transform dark:bg-gray-800 dark:border-gray-700">
                                    <x-icon name="o-camera" class="w-5 h-5 text-gray-600 dark:text-gray-300" />
                                </label>
                            </div>

                            <div class="flex-1 w-full space-y-6">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <x-input label="Full Name" wire:model="name" icon="o-user-circle" placeholder="John Doe" />
                                    <x-input label="Email Address" wire:model="email" icon="o-envelope" placeholder="john@example.com" />
                                </div>

                                <x-input label="Phone Number" wire:model="phone" icon="o-phone" placeholder="+977-9800000000" />
                                
                                <x-textarea label="Bio" wire:model="bio" placeholder="Tell us a little bit about yourself..." rows="4" hint="Maximum 500 characters" />

                                {{-- Seller Access Card --}}
                                <div class="bg-gray-50 border border-gray-100 rounded-2xl p-5 mt-6 dark:bg-gray-800/50 dark:border-gray-800">
                                    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                                        <div class="flex items-center space-x-3">
                                            <div class="w-10 h-10 bg-[#2FA084]/10 rounded-xl flex items-center justify-center shrink-0">
                                                <x-icon name="o-shopping-bag" class="w-5 h-5 text-[#2FA084]"/>
                                            </div>
                                            <div>
                                                <h4 class="text-sm font-bold text-gray-900 dark:text-gray-100">Seller Account Status</h4>
                                                @if(auth()->user()->is_seller)
                                                    <p class="text-xs text-emerald-600 font-semibold flex items-center gap-1 mt-0.5 dark:text-emerald-400">
                                                        <x-icon name="o-check-circle" class="w-4 h-4" />
                                                        <span>Active Seller — You can list products and auctions</span>
                                                    </p>
                                                @elseif(auth()->user()->seller_application_pending)
                                                    <p class="text-xs text-amber-600 font-semibold flex items-center gap-1 mt-0.5 dark:text-amber-400">
                                                        <x-icon name="o-clock" class="w-4 h-4" />
                                                        <span>Application Pending — Under admin review</span>
                                                    </p>
                                                @else
                                                    <p class="text-xs text-gray-500 font-medium mt-0.5 dark:text-gray-400">
                                                        Buyer Account — Apply to become a seller and start listing products
                                                    </p>
                                                @endif
                                            </div>
                                        </div>

                                        @if(!auth()->user()->is_seller)
                                            @if(auth()->user()->seller_application_pending)
                                                <span class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl bg-amber-50 text-amber-700 font-bold text-xs dark:bg-amber-500/10 dark:text-amber-400">
                                                    <x-icon name="o-clock" class="w-3.5 h-3.5" /> Pending Review
                                                </span>
                                            @else
                                                <x-button
                                                    label="Apply as Seller"
                                                    icon="o-user-plus"
                                                    wire:click="requestSellerAccess"
                                                    wire:loading.attr="disabled"
                                                    wire:target="requestSellerAccess"
                                                    type="button"
                                                    class="btn-primary btn-sm bg-[#1F6F5F] hover:bg-[#2FA084] text-white"
                                                />
                                            @endif
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-end pt-6 border-t border-gray-50 dark:border-gray-800">
                            <x-button label="Update Profile" type="submit" class="btn-primary px-8" spinner="updateProfile" />
                        </div>
                    </x-form>
                </div>
            @endif

            @if($activeTab === 'security')
                <div class="p-8">
                    <h3 class="text-xl font-black text-gray-900 mb-6 dark:text-gray-100">Change Password</h3>
                    
                    <x-form wire:submit="updatePassword" class="max-w-md space-y-6">
                        <x-input label="Current Password" wire:model="current_password" type="password" icon="o-key" />
                        <x-input label="New Password" wire:model="new_password" type="password" icon="o-lock-closed" />
                        <x-input label="Confirm New Password" wire:model="new_password_confirmation" type="password" icon="o-check-badge" />

                        <div class="flex justify-start pt-6">
                            <x-button label="Update Password" type="submit" class="btn-primary px-8" spinner="updatePassword" />
                        </div>
                    </x-form>
                </div>
            @endif

            @if($activeTab === 'notifications')
                <div class="p-8">
                    <h3 class="text-xl font-black text-gray-900 mb-6 dark:text-gray-100">Notification Preferences</h3>

                    <div class="space-y-6">
                        <div class="flex items-center justify-between p-4 rounded-2xl bg-gray-50 border border-gray-100 dark:bg-gray-800/50 dark:border-gray-800">
                            <div>
                                <p class="font-bold text-gray-900 dark:text-gray-100">Email Notifications</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Receive emails about your auctions and bids.</p>
                            </div>
                            <x-toggle class="toggle-primary" checked />
                        </div>

                        <div class="flex items-center justify-between p-4 rounded-2xl bg-gray-50 border border-gray-100 dark:bg-gray-800/50 dark:border-gray-800">
                            <div>
                                <p class="font-bold text-gray-900 dark:text-gray-100">Push Notifications</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">Receive real-time alerts in your browser.</p>
                            </div>
                            <x-toggle class="toggle-primary" checked />
                        </div>
                    </div>
                </div>
            @endif

        </div>
    </div>
</div>
