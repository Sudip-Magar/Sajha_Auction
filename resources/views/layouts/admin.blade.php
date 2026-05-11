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
           <livewire:components.user.topbar />

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
