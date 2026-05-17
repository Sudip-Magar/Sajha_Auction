<footer class="bg-[#0B211C] text-white pt-20 pb-10 overflow-hidden relative">
    {{-- Decorative backgrounds --}}
    <div class="absolute top-0 right-0 w-96 h-96 bg-[#2FA084]/10 rounded-full -mr-48 -mt-48 blur-3xl"></div>
    <div class="absolute bottom-0 left-0 w-64 h-64 bg-[#1F6F5F]/10 rounded-full -ml-32 -mb-32 blur-2xl"></div>

    <div class="max-w-8xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-12 mb-16">
            
            {{-- Brand Column --}}
            <div class="space-y-6">
                <a href="{{ auth()->check() ? route('dashboard') : route('home') }}" wire:navigate class="flex items-center group">
                    <div class="w-10 h-10 bg-linear-to-br from-[#1F6F5F] to-[#2FA084] rounded-xl flex items-center justify-center shadow-lg shadow-[#2FA084]/20 group-hover:scale-110 transition-transform duration-300">
                        <x-icon name="o-bolt" class="w-6 h-6 text-white" />
                    </div>
                    <span class="ml-3 text-2xl font-black tracking-tight text-white">
                        Sajha<span class="text-[#2FA084]">Auction</span>
                    </span>
                </a>
                <p class="text-gray-400 text-sm leading-relaxed max-w-xs">
                    Nepal's most trusted real-time bidding platform. We bring fairness, transparency, and excitement to online auctions.
                </p>
                <div class="flex items-center space-x-4">
                    <a href="{{ $socialLinks['facebook'] ?: '#' }}" target="_blank" rel="noopener noreferrer" class="w-9 h-9 rounded-lg bg-white/5 border border-white/10 flex items-center justify-center hover:bg-[#2FA084] hover:border-[#2FA084] transition-all duration-300">
                        <x-icon name="o-heart" class="w-4 h-4" />
                    </a>
                    <a href="{{ $socialLinks['instagram'] ?: '#' }}" target="_blank" rel="noopener noreferrer" class="w-9 h-9 rounded-lg bg-white/5 border border-white/10 flex items-center justify-center hover:bg-[#2FA084] hover:border-[#2FA084] transition-all duration-300">
                        <x-icon name="o-camera" class="w-4 h-4" />
                    </a>
                    <a href="{{ $socialLinks['twitter'] ?: '#' }}" target="_blank" rel="noopener noreferrer" class="w-9 h-9 rounded-lg bg-white/5 border border-white/10 flex items-center justify-center hover:bg-[#2FA084] hover:border-[#2FA084] transition-all duration-300">
                        <x-icon name="o-globe-alt" class="w-4 h-4" />
                    </a>
                </div>
            </div>

            {{-- Quick Links --}}
            <div>
                <h4 class="text-lg font-bold mb-6 text-white">Quick Links</h4>
                <ul class="space-y-4">
                    <li><a href="{{ auth()->check() ? route('dashboard') : route('home') }}" wire:navigate class="text-gray-400 hover:text-[#2FA084] transition-colors text-sm font-medium">Home</a></li>
                    <li><a href="#" class="text-gray-400 hover:text-[#2FA084] transition-colors text-sm font-medium">Browse Auctions</a></li>
                    <li><a href="#" class="text-gray-400 hover:text-[#2FA084] transition-colors text-sm font-medium">How it Works</a></li>
                    <li><a href="#" class="text-gray-400 hover:text-[#2FA084] transition-colors text-sm font-medium">Live Auctions</a></li>
                    <li><a href="#" class="text-gray-400 hover:text-[#2FA084] transition-colors text-sm font-medium">Upcoming Items</a></li>
                </ul>
            </div>

            {{-- Support --}}
            <div>
                <h4 class="text-lg font-bold mb-6 text-white">Support & Help</h4>
                <ul class="space-y-4">
                    <li><a href="#" class="text-gray-400 hover:text-[#2FA084] transition-colors text-sm font-medium">Help Center</a></li>
                    <li><a href="#" class="text-gray-400 hover:text-[#2FA084] transition-colors text-sm font-medium">Buying Guide</a></li>
                    <li><a href="#" class="text-gray-400 hover:text-[#2FA084] transition-colors text-sm font-medium">Selling Guide</a></li>
                    <li><a href="#" class="text-gray-400 hover:text-[#2FA084] transition-colors text-sm font-medium">Terms of Service</a></li>
                    <li><a href="#" class="text-gray-400 hover:text-[#2FA084] transition-colors text-sm font-medium">Privacy Policy</a></li>
                </ul>
            </div>

            {{-- Newsletter --}}
            <div class="space-y-6">
                <h4 class="text-lg font-bold text-white">Join our Newsletter</h4>
                <p class="text-gray-400 text-sm">Stay updated with latest auctions and exclusive offers.</p>
                <form class="relative group">
                    <input type="email" placeholder="your@email.com" 
                        class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-[#2FA084] transition-all" />
                    <button type="submit" class="absolute right-2 top-1.5 bottom-1.5 px-4 bg-[#2FA084] hover:bg-[#1F6F5F] text-white rounded-lg text-xs font-bold transition-all">
                        Join
                    </button>
                </form>
                <div class="flex items-center text-xs text-gray-500 font-medium">
                    <x-icon name="o-shield-check" class="w-4 h-4 mr-2 text-[#2FA084]" />
                    Your data is safe with us.
                </div>
            </div>
        </div>

        {{-- Bottom Bar --}}
        <div class="pt-10 border-t border-white/5 flex flex-col md:flex-row justify-between items-center space-y-4 md:space-y-0">
            <p class="text-gray-500 text-xs font-medium">
                &copy; {{ date('Y') }} Sajha Auction. All rights reserved.
            </p>
            <div class="flex items-center space-x-6 text-xs font-bold text-gray-500 uppercase tracking-widest">
                <span class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></span>
                    System Online
                </span>
                <span class="text-gray-700">|</span>
                <span>Made with <x-icon name="s-heart" class="w-3 h-3 text-red-500 inline" /> in Nepal</span>
            </div>
        </div>
    </div>
</footer>
