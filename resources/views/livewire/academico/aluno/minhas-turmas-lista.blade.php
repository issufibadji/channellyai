<div>
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-semibold text-text-primary">Minhas Turmas</h1>
        @if ($continuar['status'] === 'proximo')
            <a href="{{ $continuar['url'] }}" class="inline-flex items-center justify-center px-4 py-2 rounded-full font-medium text-sm bg-linear-to-r from-primary to-accent text-white shadow-lg shadow-primary/25 hover:brightness-110">
                Continuar estudando
            </a>
        @elseif ($continuar['status'] === 'tudo-concluido')
            <span class="text-sm text-success">Você concluiu tudo disponível até agora! 🎉</span>
        @endif
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        @forelse ($turmas as $turma)
            <a href="{{ route('academico.minha-turma.turma', $turma) }}" class="block">
                <x-card>
                    <div class="flex items-center justify-between mb-2">
                        <h2 class="text-lg font-semibold text-text-primary">{{ $turma->nome }}</h2>
                        <x-badge variant="primary">{{ $turma->curso->nome }}</x-badge>
                    </div>
                    <p class="text-sm text-text-secondary">Professor: {{ $turma->professor->name }}</p>
                </x-card>
            </a>
        @empty
            <p class="text-sm text-text-secondary">Você ainda não está matriculado em nenhuma turma.</p>
        @endforelse
    </div>
</div>
