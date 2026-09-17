<div>
    <div class="mb-6">
        <a href="{{ route('academico.minha-turma.turma', $turma) }}" class="text-sm text-primary hover:underline">&larr; {{ $turma->nome }}</a>
        <h1 class="text-2xl font-semibold text-text-primary mt-1">{{ $modulo->nome }}</h1>
    </div>

    <div class="space-y-3">
        @foreach ($itens as $item)
            @php $conteudo = $item['conteudo']; @endphp

            @if ($item['disponivel'])
                <div x-data="{ open: false }">
                    <button @click="open = !open" type="button" class="w-full text-left">
                        <x-card class="hover:border-primary/50 transition">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    @if ($item['concluido'])
                                        <x-heroicon-s-check-circle class="w-4 h-4 text-success" />
                                    @endif
                                    <span class="text-text-primary font-medium">{{ $conteudo->titulo }}</span>
                                </div>
                                <x-badge variant="primary">{{ $conteudo->tipo }}</x-badge>
                            </div>
                        </x-card>
                    </button>

                    <div x-show="open" x-transition class="mt-2 px-1 space-y-3">
                        @if ($conteudo->tipo === 'video' && $conteudo->arquivo_path)
                            <video controls class="w-full rounded-lg" src="{{ asset('storage/'.$conteudo->arquivo_path) }}"></video>
                        @elseif ($conteudo->tipo === 'pdf' && $conteudo->arquivo_path)
                            <iframe src="{{ asset('storage/'.$conteudo->arquivo_path) }}" class="w-full h-[70vh] rounded-lg border border-surface-border"></iframe>
                        @elseif ($conteudo->tipo === 'link' && $conteudo->url_externa)
                            <a href="{{ $conteudo->url_externa }}" target="_blank" rel="noopener" class="text-primary hover:underline text-sm">
                                Abrir link externo &rarr;
                            </a>
                        @elseif ($conteudo->corpo)
                            <p class="text-sm text-text-secondary whitespace-pre-line">{{ $conteudo->corpo }}</p>
                        @else
                            <p class="text-sm text-text-secondary">Nenhum material anexado ainda.</p>
                        @endif

                        <x-button
                            wire:click="toggleConclusao({{ $conteudo->id }})"
                            :variant="$item['concluido'] ? 'secondary' : 'primary'"
                        >
                            {{ $item['concluido'] ? 'Concluído ✓ (desmarcar)' : 'Marcar como concluído' }}
                        </x-button>
                    </div>
                </div>
            @else
                <x-card class="opacity-60">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <x-heroicon-o-lock-closed class="w-4 h-4 text-text-secondary" />
                            <span class="text-text-primary font-medium">{{ $conteudo->titulo }}</span>
                        </div>
                        <x-badge variant="warning">
                            {{ $item['diasRestantes'] > 0 ? "Libera em {$item['diasRestantes']} dia(s)" : 'Bloqueado' }}
                        </x-badge>
                    </div>
                </x-card>
            @endif
        @endforeach
    </div>
</div>
