<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
    <div class="min-h-screen flex items-center justify-center p-6">
        <div class="glow-border w-full max-w-4xl grid grid-cols-1 lg:grid-cols-2 bg-surface-card border border-surface-border rounded-2xl overflow-hidden">
            <div class="p-8 sm:p-10 flex flex-col justify-center">
                <div class="flex items-center gap-3 mb-8">
                    <div class="w-10 h-10 rounded-xl bg-linear-to-br from-primary to-accent flex items-center justify-center shadow-lg shadow-primary/30 overflow-hidden">
                        @if ($logoUrl = config_app_media('app_logo'))
                            <img src="{{ $logoUrl }}" class="w-full h-full object-cover" alt="Logo">
                        @else
                            <x-heroicon-s-sparkles class="w-5 h-5 text-white" />
                        @endif
                    </div>
                    <span class="text-lg font-semibold text-text-primary">{{ config_app('app_name', config('app.name')) }}</span>
                </div>

                <x-flash-messages />

                {{ $slot }}
            </div>

            <div class="hidden lg:flex relative flex-col justify-between p-10 overflow-hidden bg-linear-to-br from-primary via-primary-dark to-surface">
                <div
                    class="absolute inset-0"
                    style="background-image:
                        radial-gradient(ellipse 480px 420px at 15% 15%, oklch(80% 0.14 205 / 55%), transparent 60%),
                        radial-gradient(ellipse 420px 460px at 90% 30%, oklch(70% 0.18 250 / 50%), transparent 55%),
                        radial-gradient(ellipse 520px 480px at 40% 100%, oklch(30% 0.1 260 / 70%), transparent 60%);"
                ></div>

                <div class="relative">
                    <p class="text-white/70 text-sm uppercase tracking-widest">Bem-vindo</p>
                    <h2 class="text-white text-3xl font-semibold mt-2 leading-tight">
                        Gestão inteligente,<br>em um só lugar.
                    </h2>
                </div>

                <p class="relative text-white/60 text-sm">
                    &copy; {{ date('Y') }} {{ config_app('app_name', config('app.name')) }}. Todos os direitos reservados.
                </p>
            </div>
        </div>
    </div>

    @livewireScripts
</body>
</html>
