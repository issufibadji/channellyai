<div class="p-6">
    <h1 class="text-2xl font-semibold">Dashboard</h1>
    <p class="text-sm text-gray-600 mt-2">Bem-vindo, {{ auth()->user()->name }}.</p>

    <div class="mt-6">
        <a href="{{ route('settings.two-factor') }}" class="text-sm text-blue-600 hover:underline">
            Verificação em duas etapas
        </a>
        ·
        <form method="POST" action="{{ route('logout') }}" class="inline">
            @csrf
            <button type="submit" class="text-sm text-blue-600 hover:underline">Sair</button>
        </form>
    </div>
</div>
