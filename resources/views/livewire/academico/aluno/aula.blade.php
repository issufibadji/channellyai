<div class="max-w-4xl mx-auto">
    <div class="mb-6">
        <a href="{{ route('academico.minha-turma.modulo', [$turma, $modulo]) }}" class="text-sm text-primary hover:underline">&larr; {{ $modulo->nome }}</a>
        <div class="flex items-center gap-3 mt-2 flex-wrap">
            <h1 class="text-2xl font-semibold text-text-primary">{{ $conteudo->titulo }}</h1>
            <x-badge variant="primary">{{ $conteudo->rotuloTipo() }}</x-badge>
            @if ($concluido)
                <span class="inline-flex items-center gap-1 text-sm text-success">
                    <x-heroicon-s-check-circle class="w-4 h-4" /> Concluída
                </span>
            @endif
        </div>
    </div>

    @if (! $disponivel)
        <x-card>
            <div class="flex flex-col items-center text-center py-10 gap-2">
                <x-heroicon-o-lock-closed class="w-10 h-10 text-text-secondary" />
                <p class="text-text-primary font-medium">
                    {{ $diasRestantes > 0 ? "Esta aula libera em {$diasRestantes} dia(s)." : 'Esta aula está bloqueada no momento.' }}
                </p>
            </div>
        </x-card>
    @else
        <div class="space-y-4">
            @switch(true)
                @case(in_array($conteudo->tipo, ['video', 'video_curto'], true))
                    @if ($conteudo->embedUrlVideo())
                        <iframe src="{{ $conteudo->embedUrlVideo() }}" class="w-full aspect-video rounded-2xl border border-surface-border bg-black" allow="autoplay; fullscreen" allowfullscreen></iframe>
                    @elseif ($conteudo->url_externa)
                        <x-card>
                            <a href="{{ $conteudo->url_externa }}" target="_blank" rel="noopener" class="text-primary hover:underline text-sm">Abrir vídeo &rarr;</a>
                        </x-card>
                    @else
                        <x-card><p class="text-sm text-text-secondary">Nenhum vídeo cadastrado ainda.</p></x-card>
                    @endif
                    @break

                @case($conteudo->tipo === 'texto')
                    <x-card>
                        <p class="text-sm text-text-secondary whitespace-pre-line">{{ $conteudo->corpo ?? 'Nenhum conteúdo cadastrado ainda.' }}</p>
                    </x-card>
                    @break

                @case($conteudo->tipo === 'link')
                    <x-card>
                        @if ($conteudo->url_externa)
                            <a href="{{ $conteudo->url_externa }}" target="_blank" rel="noopener" class="text-primary hover:underline text-sm">Abrir link externo &rarr;</a>
                        @else
                            <p class="text-sm text-text-secondary">Nenhum link cadastrado ainda.</p>
                        @endif
                    </x-card>
                    @break

                @case($ehQuiz)
                    @if ($concluido)
                        <x-card>
                            <p class="text-text-primary font-medium">Sua nota: {{ $score }}%</p>
                            <button type="button" wire:click="refazerQuiz" class="text-primary hover:underline text-sm mt-2">Refazer o quiz</button>
                        </x-card>
                    @else
                        <div class="space-y-4">
                            @foreach ($conteudo->perguntas as $pergunta)
                                <x-card>
                                    <p class="text-text-primary font-medium mb-3">{{ $pergunta->enunciado }}</p>
                                    <div class="space-y-2">
                                        @foreach ($pergunta->opcoes as $opcao)
                                            <label class="flex items-center gap-2 text-sm text-text-secondary">
                                                <input type="checkbox" wire:model="respostasSelecionadas.{{ $pergunta->id }}.{{ $opcao->id }}" class="rounded bg-surface border-surface-border">
                                                {{ $opcao->texto }}
                                            </label>
                                        @endforeach
                                    </div>
                                </x-card>
                            @endforeach
                            <x-button wire:click="enviarQuiz">Enviar respostas</x-button>
                        </div>
                    @endif
                    @break

                @case($conteudo->tipo === 'pdf')
                    @if ($conteudo->arquivo_path)
                        <div class="rounded-2xl overflow-hidden border border-surface-border bg-surface-card">
                            <iframe src="{{ asset('storage/'.$conteudo->arquivo_path) }}" class="w-full h-[75vh]"></iframe>
                        </div>
                        <div class="flex justify-end">
                            <a href="{{ asset('storage/'.$conteudo->arquivo_path) }}" target="_blank" rel="noopener" class="inline-flex items-center gap-2 text-primary hover:underline text-sm">
                                <x-heroicon-o-arrow-down-tray class="w-4 h-4" /> Baixar PDF
                            </a>
                        </div>
                    @else
                        <x-card><p class="text-sm text-text-secondary">Nenhum arquivo anexado ainda.</p></x-card>
                    @endif
                    @break

                @default
                    {{-- exercício-anexo: pdf, doc ou docx. Doc/docx não renderiza no navegador, mas
                         se o professor anexou um PDF aqui, mostra o mesmo visualizador embutido. --}}
                    @if ($conteudo->arquivo_path && str_ends_with(strtolower($conteudo->arquivo_path), '.pdf'))
                        <div class="rounded-2xl overflow-hidden border border-surface-border bg-surface-card">
                            <iframe src="{{ asset('storage/'.$conteudo->arquivo_path) }}" class="w-full h-[75vh]"></iframe>
                        </div>
                        <div class="flex justify-end">
                            <a href="{{ asset('storage/'.$conteudo->arquivo_path) }}" target="_blank" rel="noopener" class="inline-flex items-center gap-2 text-primary hover:underline text-sm">
                                <x-heroicon-o-arrow-down-tray class="w-4 h-4" /> Baixar PDF
                            </a>
                        </div>
                    @elseif ($conteudo->arquivo_path)
                        <x-card>
                            <a href="{{ asset('storage/'.$conteudo->arquivo_path) }}" target="_blank" rel="noopener" class="inline-flex items-center gap-2 text-primary hover:underline text-sm">
                                <x-heroicon-o-arrow-down-tray class="w-4 h-4" /> Abrir / baixar arquivo
                            </a>
                        </x-card>
                    @else
                        <x-card><p class="text-sm text-text-secondary">Nenhum arquivo anexado ainda.</p></x-card>
                    @endif
            @endswitch

            @unless ($ehQuiz)
                <div class="flex justify-end">
                    <x-button wire:click="toggleConclusao" :variant="$concluido ? 'secondary' : 'primary'">
                        {{ $concluido ? 'Concluída ✓ (desmarcar)' : 'Marcar como concluída' }}
                    </x-button>
                </div>
            @endunless
        </div>
    @endif

    <div class="flex items-center justify-between mt-8 pt-4 border-t border-surface-border text-sm">
        @if ($vizinhos['anterior'])
            <a href="{{ route('academico.minha-turma.aula', [$turma, $vizinhos['anterior']]) }}" class="text-primary hover:underline">&larr; Aula anterior</a>
        @else
            <span class="text-text-secondary/50">&larr; Aula anterior</span>
        @endif

        @if ($vizinhos['proximo'])
            <a href="{{ route('academico.minha-turma.aula', [$turma, $vizinhos['proximo']]) }}" class="text-primary hover:underline">Próxima aula &rarr;</a>
        @else
            <span class="text-text-secondary/50">Próxima aula &rarr;</span>
        @endif
    </div>
</div>
