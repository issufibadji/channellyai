<div class="max-w-4xl mx-auto">
    <div class="mb-6">
        <a href="{{ route('academico.minha-turma.turma', $turma) }}" class="text-sm text-primary hover:underline">&larr; {{ $turma->nome }}</a>
        <h1 class="text-2xl font-semibold text-text-primary mt-1">{{ $modulo->nome }}</h1>
        @if ($modulo->secao)
            <p class="text-sm text-text-secondary">{{ $modulo->secao }}</p>
        @endif
    </div>

    <div class="space-y-3">
        @forelse ($itens as $item)
            @php $conteudo = $item['conteudo']; @endphp

            @if ($item['disponivel'])
                <a href="{{ route('academico.minha-turma.aula', [$turma, $conteudo]) }}" class="block">
                    <x-card class="hover:border-primary/50 transition">
                        <div class="flex items-center gap-4">
                            <span class="w-9 h-9 rounded-full bg-primary/15 flex items-center justify-center text-xs font-semibold text-accent shrink-0">
                                {{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}
                            </span>
                            <x-icon :name="'heroicon-o-'.$conteudo->icone()" class="w-5 h-5 text-text-secondary shrink-0" />
                            <span class="flex-1 text-text-primary font-medium">{{ $conteudo->titulo }}</span>
                            <x-badge variant="primary">{{ $conteudo->rotuloTipo() }}</x-badge>
                            @if ($item['concluido'])
                                <x-heroicon-s-check-circle class="w-5 h-5 text-success shrink-0" />
                            @endif
                        </div>
                    </x-card>
                </a>
            @else
                <x-card class="opacity-60">
                    <div class="flex items-center gap-4">
                        <x-heroicon-o-lock-closed class="w-5 h-5 text-text-secondary shrink-0" />
                        <span class="flex-1 text-text-primary font-medium">{{ $conteudo->titulo }}</span>
                        <x-badge variant="warning">
                            {{ $item['diasRestantes'] > 0 ? "Libera em {$item['diasRestantes']} dia(s)" : 'Bloqueado' }}
                        </x-badge>
                    </div>
                </x-card>
            @endif
        @empty
            <p class="text-sm text-text-secondary">Nenhuma aula publicada neste módulo ainda.</p>
        @endforelse
    </div>
</div>
