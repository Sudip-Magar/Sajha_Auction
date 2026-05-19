<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">

        <title>{{ $title ?? config('app.name') }}</title>

        <meta name="csrf-token" content="{{ csrf_token() }}">

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        @livewireStyles
    </head>
    <body>
        <livewire:components.user.navbar />
        {{ $slot }}

        <livewire:components.user.footer />

        <x-toast />
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
