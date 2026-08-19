<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="vapid-key" content="{{ config('webpush.vapid.public_key') }}">
    <title>{{ $title ?? config_app('app_name', config('app.name')) }}</title>

    <script>
        if (localStorage.getItem('theme') === 'light') {
            document.documentElement.classList.add('light');
        }
    </script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Kode+Mono:wght@400;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="bg-surface text-text-primary antialiased">
    <div class="flex min-h-screen">
        <livewire:sidebar />

        <main class="flex-1 min-w-0 overflow-x-hidden">
            <div
                class="sticky top-0 z-30 flex justify-end items-center gap-2 px-6 py-3 border-b border-surface-border bg-surface-card/40 backdrop-blur"
                x-data="{ light: document.documentElement.classList.contains('light') }"
            >
                <button
                    type="button"
                    @click="light = !light; document.documentElement.classList.toggle('light', light); localStorage.setItem('theme', light ? 'light' : 'dark')"
                    class="p-2 rounded-lg hover:bg-surface-border text-text-secondary hover:text-text-primary transition"
                    title="Alternar tema claro/escuro"
                >
                    <x-heroicon-o-moon x-show="!light" class="w-5 h-5" />
                    <x-heroicon-o-sun x-show="light" x-cloak class="w-5 h-5" />
                </button>

                <livewire:notification-bell />
            </div>

            <div class="p-6">
                <div class="max-w-7xl mx-auto">
                    <x-flash-messages />

                    {{ $slot }}
                </div>
            </div>
        </main>
    </div>

    @livewireScripts
</body>
</html>
