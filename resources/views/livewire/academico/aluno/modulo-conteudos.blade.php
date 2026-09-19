<div>
    <div class="mb-6">
        <a href="{{ route('academico.minha-turma.turma', $turma) }}" class="text-sm text-primary hover:underline">&larr; {{ $turma->nome }}</a>
        <h1 class="text-2xl font-semibold text-text-primary mt-1">{{ $modulo->nome }}</h1>
    </div>

    <div class="space-y-3">
        @foreach ($itens as $item)
            @php
                $conteudo = $item['conteudo'];
                $ehQuiz = $conteudo->tipo === 'exercicio' && $conteudo->exercicio_subtipo === 'quiz';
                $ehAnexo = $conteudo->tipo === 'pdf' || ($conteudo->tipo === 'exercicio' && $conteudo->exercicio_subtipo === 'anexo');
                $embedUrl = $conteudo->tipo === 'video' ? $conteudo->embedUrlVideo() : null;
            @endphp

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
                        @if ($conteudo->tipo === 'texto')
                            <p class="text-sm text-text-secondary whitespace-pre-line">{{ $conteudo->corpo ?? 'Nenhum conteúdo cadastrado ainda.' }}</p>
                        @elseif ($conteudo->tipo === 'video')
                            @if ($embedUrl)
                                <iframe src="{{ $embedUrl }}" class="w-full aspect-video rounded-lg border border-surface-border" allowfullscreen></iframe>
                            @elseif ($conteudo->url_externa)
                                <a href="{{ $conteudo->url_externa }}" target="_blank" rel="noopener" class="text-primary hover:underline text-sm">
                                    Abrir vídeo &rarr;
                                </a>
                            @else
                                <p class="text-sm text-text-secondary">Nenhum vídeo cadastrado ainda.</p>
                            @endif
                        @elseif ($conteudo->tipo === 'link')
                            @if ($conteudo->url_externa)
                                <a href="{{ $conteudo->url_externa }}" target="_blank" rel="noopener" class="text-primary hover:underline text-sm">
                                    Abrir link externo &rarr;
                                </a>
                            @else
                                <p class="text-sm text-text-secondary">Nenhum link cadastrado ainda.</p>
                            @endif
                        @elseif ($ehAnexo)
                            @if ($conteudo->arquivo_path)
                                <a href="{{ asset('storage/'.$conteudo->arquivo_path) }}" target="_blank" rel="noopener" class="inline-flex items-center gap-2 text-primary hover:underline text-sm">
                                    <x-heroicon-o-arrow-down-tray class="w-4 h-4" />
                                    Abrir / baixar arquivo
                                </a>
                            @else
                                <p class="text-sm text-text-secondary">Nenhum arquivo anexado ainda.</p>
                            @endif
                        @elseif ($ehQuiz)
                            @if ($item['concluido'])
                                <x-card>
                                    <p class="text-sm text-text-primary font-medium">Sua nota: {{ $item['score'] }}%</p>
                                    <button type="button" wire:click="refazerQuiz({{ $conteudo->id }})" class="text-primary hover:underline text-xs mt-1">
                                        Refazer o quiz
                                    </button>
                                </x-card>
                            @endif

                            @if (! $item['concluido'])
                                <div class="space-y-4">
                                    @foreach ($conteudo->perguntas as $pergunta)
                                        <div class="rounded-lg border border-surface-border p-3">
                                            <p class="text-sm text-text-primary font-medium mb-2">{{ $pergunta->enunciado }}</p>
                                            <div class="space-y-1">
                                                @foreach ($pergunta->opcoes as $opcao)
                                                    <label class="flex items-center gap-2 text-sm text-text-secondary">
                                                        <input
                                                            type="checkbox"
                                                            wire:model="respostasSelecionadas.{{ $pergunta->id }}"
                                                            value="{{ $opcao->id }}"
                                                            class="rounded bg-surface border-surface-border"
                                                        >
                                                        {{ $opcao->texto }}
                                                    </label>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endforeach

                                    <x-button wire:click="enviarQuiz({{ $conteudo->id }})">Enviar respostas</x-button>
                                </div>
                            @endif
                        @else
                            <p class="text-sm text-text-secondary">Nenhum material anexado ainda.</p>
                        @endif

                        <div class="flex items-center justify-between pt-2">
                            <div class="flex gap-3 text-sm">
                                @if ($item['vizinhos']['anterior'])
                                    <a href="{{ route('academico.minha-turma.modulo', [$turma, $item['vizinhos']['anterior']->modulo]) }}" class="text-primary hover:underline">
                                        &larr; Aula anterior
                                    </a>
                                @else
                                    <span class="text-text-secondary/50">&larr; Aula anterior</span>
                                @endif

                                @if ($item['vizinhos']['proximo'])
                                    <a href="{{ route('academico.minha-turma.modulo', [$turma, $item['vizinhos']['proximo']->modulo]) }}" class="text-primary hover:underline">
                                        Próxima aula &rarr;
                                    </a>
                                @else
                                    <span class="text-text-secondary/50">Próxima aula &rarr;</span>
                                @endif
                            </div>

                            @unless ($ehQuiz)
                                <x-button
                                    wire:click="toggleConclusao({{ $conteudo->id }})"
                                    :variant="$item['concluido'] ? 'secondary' : 'primary'"
                                >
                                    {{ $item['concluido'] ? 'Concluído ✓ (desmarcar)' : 'Marcar como concluído' }}
                                </x-button>
                            @endunless
                        </div>
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
