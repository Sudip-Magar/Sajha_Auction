<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>{{ $title ?? config('app.name') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @livewireStyles
</head>

<body class="bg-gray-50 antialiased text-gray-900 font-sans">
    <!-- Wrap everything in a single Alpine context -->
    <div x-data class="flex min-h-screen relative">
        <!-- Sidebar -->
        <livewire:components.admin.sidebar />

        <!-- Spacer for Sidebar (Desktop) — must mirror sidebar width exactly -->
        <div class="hidden lg:block shrink-0 transition-all duration-300"
             x-bind:class="$store.adminSidebar.collapsed ? 'w-20' : 'w-72'"></div>

        <!-- Main Content Area -->
        <main class="flex-1 transition-all duration-300 min-w-0">
            <header class="sticky top-0 z-30 bg-linear-to-r from-[#2FA084] to-[#1F6F5F] backdrop-blur-md border-b border-gray-100 shadow-sm h-12 flex items-center px-8 lg:px-12 justify-between">
                <h2 class="text-xl font-bold text-gray-800">{{ $title ?? 'Dashboard' }}</h2>
                <div class="flex items-center space-x-4">
                    <button class="p-2 text-gray-400 hover:text-[#2FA084] transition-colors">
                        <x-icon name="o-bell" class="w-6 h-6" />
                    </button>
                </div>
            </header>

            <div class="p-8 lg:px-7 lg:py-4 max-w-full mx-auto">
                {{ $slot }}
            </div>
        </main>
    </div>

    <x-toast />
    @livewireScripts
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.store('adminSidebar', {
                collapsed: false,
                toggle() {
                    this.collapsed = !this.collapsed;
                }
            });
        });
    </script>
</body>

</html>
