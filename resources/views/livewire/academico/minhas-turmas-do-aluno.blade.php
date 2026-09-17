<div>
    <h1 class="text-2xl font-semibold text-text-primary mb-6">Minhas Turmas</h1>

    <div class="space-y-6">
        @forelse ($turmas as $turma)
            <x-card :title="$turma->nome" :badge="$turma->curso->nome">
                <p class="text-sm text-text-secondary mb-4">Professor: {{ $turma->professor->name }}</p>

                @forelse ($turma->modulos as $modulo)
                    <div class="mb-4">
                        <p class="text-sm font-medium text-text-primary mb-1">{{ $modulo->nome }} · {{ $modulo->nivel }}</p>
                        <ul class="space-y-1">
                            @foreach ($modulo->conteudos as $conteudo)
                                <li class="text-sm text-text-secondary pl-3 border-l border-surface-border">
                                    {{ $conteudo->titulo }}
                                    <x-badge>{{ $conteudo->tipo }}</x-badge>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @empty
                    <p class="text-sm text-text-secondary">Nenhum módulo publicado ainda nesta turma.</p>
                @endforelse
            </x-card>
        @empty
            <p class="text-sm text-text-secondary">Você ainda não está matriculado em nenhuma turma.</p>
        @endforelse
    </div>
</div>
