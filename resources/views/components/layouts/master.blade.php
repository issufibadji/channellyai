<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="vapid-key" content="{{ config('webpush.vapid.public_key') }}">
    <title>{{ $title ?? config('app.name') }}</title>

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
            <div class="flex justify-end items-center px-6 py-3 border-b border-surface-border">
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
