<div>
    <h1 class="text-2xl font-semibold text-text-primary mb-6">Minhas Turmas</h1>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        @forelse ($turmas as $turma)
            <x-card>
                <div class="flex items-center justify-between mb-2">
                    <h2 class="text-lg font-semibold text-text-primary">{{ $turma->nome }}</h2>
                    <x-badge variant="primary">{{ $turma->alunos_count }} {{ $turma->alunos_count === 1 ? 'aluno' : 'alunos' }}</x-badge>
                </div>
                <p class="text-sm text-text-secondary mb-4">{{ $turma->curso->nome }}</p>

                <div class="flex gap-4 text-sm">
                    <a href="{{ route('academico.turmas.conteudo', $turma) }}" class="text-primary hover:underline">
                        Módulos e conteúdo ({{ $turma->modulos->count() }})
                    </a>
                    <a href="{{ route('academico.turmas.ao-vivo', $turma) }}" class="text-primary hover:underline">
                        Aulas ao vivo
                    </a>
                    @can('manage-matriculas')
                        <a href="{{ route('academico.turmas.matricula', $turma) }}" class="text-primary hover:underline">
                            Matrícula
                        </a>
                    @endcan
                </div>
            </x-card>
        @empty
            <p class="text-sm text-text-secondary">Você ainda não é responsável por nenhuma turma.</p>
        @endforelse
    </div>
</div>
