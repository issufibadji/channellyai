<div>
    <div class="mb-6">
        <a href="{{ route('academico.minha-turma.index') }}" class="text-sm text-primary hover:underline">&larr; Minhas Turmas</a>
        <h1 class="text-2xl font-semibold text-text-primary mt-1">{{ $turma->nome }}</h1>
    </div>

    <div class="flex gap-2 mb-6 border-b border-surface-border">
        @foreach ($abas as $aba)
            @php
                $labels = ['basico' => 'Básico', 'intermediario' => 'Intermediário', 'avancado' => 'Avançado'];
            @endphp
            <button
                wire:click="selecionarAba('{{ $aba }}')"
                class="px-4 py-2 text-sm font-medium border-b-2 -mb-px {{ $abaAtiva === $aba ? 'border-primary text-primary' : 'border-transparent text-text-secondary hover:text-text-primary' }}"
            >
                {{ $labels[$aba] }}
            </button>
        @endforeach
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-10">
        @forelse ($modulosNivel as $modulo)
            <a href="{{ route('academico.minha-turma.modulo', [$turma, $modulo]) }}" class="block">
                <x-card>
                    <div class="flex items-center justify-between">
                        <h2 class="text-base font-semibold text-text-primary">{{ $modulo->nome }}</h2>
                        <x-badge>{{ $modulo->nivel }}</x-badge>
                    </div>
                    <p class="text-sm text-text-secondary mt-1">{{ $modulo->conteudos_count }} conteúdo(s)</p>
                </x-card>
            </a>
        @empty
            <p class="text-sm text-text-secondary">Nenhum módulo publicado neste nível ainda.</p>
        @endforelse
    </div>

    <h2 class="text-lg font-semibold text-text-primary mb-4">Atividades Extras</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        @forelse ($modulosExtra as $modulo)
            <a href="{{ route('academico.minha-turma.modulo', [$turma, $modulo]) }}" class="block">
                <x-card>
                    <h2 class="text-base font-semibold text-text-primary">{{ $modulo->nome }}</h2>
                    <p class="text-sm text-text-secondary mt-1">{{ $modulo->conteudos_count }} conteúdo(s)</p>
                </x-card>
            </a>
        @empty
            <p class="text-sm text-text-secondary">Nenhuma atividade extra publicada ainda.</p>
        @endforelse
    </div>
</div>
