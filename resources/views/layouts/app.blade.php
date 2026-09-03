<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>{{ $title ?? config('app.name') }}</title>

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <script>
        (() => {
            const storedTheme = localStorage.getItem('theme');
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            const isDark = storedTheme === 'dark' || (!storedTheme && prefersDark);

            document.documentElement.classList.toggle('dark', isDark);
            // Keep daisyUI's own theme (used by inputs, buttons, checkboxes, etc.)
            // in sync with the app's dark-mode class, instead of daisyUI falling
            // back to the OS color scheme independently of this toggle.
            document.documentElement.setAttribute('data-theme', isDark ? 'dark' : 'light');
        })();
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @livewireStyles
</head>
<body class="bg-white text-gray-950 transition-colors dark:bg-[#101114] dark:text-gray-100">
<livewire:components.user.navbar/>

{{ $slot }}

<livewire:components.user.footer/>
<livewire:components.user.auction-notice />
<x-toast/>
@livewireScripts

@auth
    <script>
        (() => {
            if (window.__userNotificationBootstrapReady) {
                return;
            }

            window.__userNotificationBootstrapReady = true;

            const userId = '{{ auth()->id() }}';
            const channelName = `App.Models.User.${userId}`;

            const subscribe = () => {
                if (window.__userNotificationChannel) {
                    return;
                }

                if (!window.Echo) {
                    if (window.__userNotificationRetryTimer) {
                        return;
                    }

                    window.__userNotificationRetryTimer = setTimeout(() => {
                        window.__userNotificationRetryTimer = null;
                        subscribe();
                    }, 500);

                    return;
                }

                window.__userNotificationChannel = window.Echo.private(channelName)
                    .notification(() => {
                        Livewire.dispatch('userNotificationReceived');
                    })
                    .error(() => {
                        window.__userNotificationChannel = null;
                    });
            };

            document.addEventListener('DOMContentLoaded', subscribe);
            document.addEventListener('livewire:navigated', subscribe);

            subscribe();
        })();
    </script>
@endauth
</body>

</html>
