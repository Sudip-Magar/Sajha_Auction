<div>
    <x-header title="System Settings" subtitle="Configure global platform parameters" separator progress-indicator />

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
        <!-- Sidebar Tabs -->
        <div class="lg:col-span-1 space-y-2">
            <x-button label="General" icon="o-cog-6-tooth" 
                class="w-full justify-start {{ $activeTab === 'general' ? 'btn-primary' : 'btn-ghost' }}" 
                wire:click="$set('activeTab', 'general')" />
            <x-button label="Social Media" icon="o-share" 
                class="w-full justify-start {{ $activeTab === 'social' ? 'btn-primary' : 'btn-ghost' }}" 
                wire:click="$set('activeTab', 'social')" />
            <x-button label="Security" icon="o-shield-check" 
                class="w-full justify-start {{ $activeTab === 'security' ? 'btn-primary' : 'btn-ghost' }}" 
                wire:click="$set('activeTab', 'security')" />
        </div>

        <!-- Content Area -->
        <div class="lg:col-span-3">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8">
                
                @if($activeTab === 'general')
                    <x-form wire:submit="saveGeneral" class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <x-input label="Site Name" wire:model="site_name" icon="o-globe-alt" />
                            <x-input label="Site Email" wire:model="site_email" icon="o-envelope" />
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <x-input label="Contact Phone" wire:model="contact_phone" icon="o-phone" />
                            <x-input label="Business Address" wire:model="address" icon="o-map-pin" />
                        </div>

                        <div class="divider"></div>

                        <x-toggle label="Maintenance Mode" wire:model="maintenance_mode" hint="Prevent users from accessing the site" />

                        <x-slot:actions>
                            <x-button label="Save General Settings" type="submit" class="btn-primary" spinner="saveGeneral" />
                        </x-slot:actions>
                    </x-form>
                @endif

                @if($activeTab === 'social')
                    <x-form wire:submit="saveSocial" class="space-y-6">
                        <x-input label="Facebook URL" wire:model="facebook_url" icon="o-link" placeholder="https://facebook.com/..." />
                        <x-input label="Twitter (X) URL" wire:model="twitter_url" icon="o-link" placeholder="https://twitter.com/..." />
                        <x-input label="Instagram URL" wire:model="instagram_url" icon="o-link" placeholder="https://instagram.com/..." />

                        <x-slot:actions>
                            <x-button label="Save Social Links" type="submit" class="btn-primary" spinner="saveSocial" />
                        </x-slot:actions>
                    </x-form>
                @endif

                @if($activeTab === 'security')
                    <x-form wire:submit="saveSecurity" class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <x-input label="Session Timeout (minutes)" wire:model="session_timeout" type="number" icon="o-clock" />
                            <x-input label="Minimum Password Length" wire:model="password_min_length" type="number" icon="o-key" />
                        </div>

                        <div class="space-y-4">
                            <x-toggle
                                label="Require Strong Passwords"
                                wire:model="require_strong_password"
                                hint="Enforce uppercase, lowercase, number, and special characters."
                            />

                            <x-toggle
                                label="Require Two-Factor Authentication"
                                wire:model="two_factor_required"
                                hint="Mark 2FA as mandatory for sign in."
                            />
                        </div>

                        <x-slot:actions>
                            <x-button label="Save Security Settings" type="submit" class="btn-primary" spinner="saveSecurity" />
                        </x-slot:actions>
                    </x-form>
                @endif

            </div>
        </div>
    </div>
</div>
