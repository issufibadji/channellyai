<div>
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-semibold text-text-primary">{{ $turma->nome }}</h1>
            <p class="text-sm text-text-secondary">Módulos e conteúdo</p>
        </div>
        <x-button wire:click="createModulo">+ Módulo</x-button>
    </div>

    <div class="space-y-4">
        @forelse ($modulos as $modulo)
            <x-card>
                <div class="flex items-center justify-between mb-3">
                    <div>
                        <h2 class="text-lg font-semibold text-text-primary">{{ $modulo->nome }}</h2>
                        <span class="text-xs text-text-secondary">
                            {{ $modulo->categoria === 'extra' ? 'Atividade extra' : 'Nível '.$modulo->nivel }} · ordem {{ $modulo->ordem }}
                        </span>
                    </div>
                    <div class="space-x-3 text-sm">
                        <button wire:click="createConteudo({{ $modulo->id }})" class="text-primary hover:underline">+ Conteúdo</button>
                        <button wire:click="editModulo({{ $modulo->id }})" class="text-primary hover:underline">Editar</button>
                        <button
                            wire:click="deleteModulo({{ $modulo->id }})"
                            wire:confirm="Remover este módulo e todo o conteúdo dele?"
                            class="text-danger hover:underline"
                        >
                            Excluir
                        </button>
                    </div>
                </div>

                <x-table :headers="['Título', 'Tipo', 'Ordem', 'Ações']">
                    @foreach ($modulo->conteudos as $conteudo)
                        <tr>
                            <td class="px-4 py-3">{{ $conteudo->titulo }}</td>
                            <td class="px-4 py-3">
                                <x-badge variant="primary">{{ $conteudo->tipo }}</x-badge>
                                @if ($conteudo->tipo === 'exercicio' && $conteudo->exercicio_subtipo)
                                    <x-badge>{{ $conteudo->exercicio_subtipo }}</x-badge>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-text-secondary">{{ $conteudo->ordem }}</td>
                            <td class="px-4 py-3 text-right whitespace-nowrap space-x-3">
                                <button wire:click="editConteudo({{ $conteudo->id }})" class="text-primary hover:underline text-sm">Editar</button>
                                <button
                                    wire:click="deleteConteudo({{ $conteudo->id }})"
                                    wire:confirm="Remover este conteúdo?"
                                    class="text-danger hover:underline text-sm"
                                >
                                    Excluir
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </x-table>
            </x-card>
        @empty
            <p class="text-sm text-text-secondary">Nenhum módulo cadastrado ainda.</p>
        @endforelse
    </div>

    <x-modal name="modulo-form" title="Módulo">
        <form wire:submit="saveModulo" class="space-y-4">
            <div>
                <label class="block text-sm font-medium mb-1 text-text-primary">Nome</label>
                <input type="text" wire:model="moduloNome" class="w-full rounded-md bg-surface border-surface-border text-text-primary">
                @error('moduloNome') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium mb-1 text-text-primary">Categoria</label>
                <select wire:model.live="categoria" class="w-full rounded-md bg-surface border-surface-border text-text-primary">
                    <option value="nivel">Nível (Básico/Intermediário/Avançado)</option>
                    <option value="extra">Atividade extra</option>
                </select>
            </div>

            @if ($categoria === 'nivel')
                <div>
                    <label class="block text-sm font-medium mb-1 text-text-primary">Nível</label>
                    <select wire:model="nivel" class="w-full rounded-md bg-surface border-surface-border text-text-primary">
                        @foreach (['A1', 'A2', 'B1', 'B2', 'C1', 'C2'] as $opcao)
                            <option value="{{ $opcao }}">{{ $opcao }}</option>
                        @endforeach
                    </select>
                    @error('nivel') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
                </div>
            @endif

            <div>
                <label class="block text-sm font-medium mb-1 text-text-primary">Seção (agrupamento na vitrine do aluno)</label>
                <input type="text" wire:model="secao" list="secoes-existentes" placeholder="ex.: Mapas Mentais, Vídeos Curtos" class="w-full rounded-md bg-surface border-surface-border text-text-primary">
                <datalist id="secoes-existentes">
                    @foreach ($secoesExistentes as $secaoExistente)
                        <option value="{{ $secaoExistente }}"></option>
                    @endforeach
                </datalist>
                <p class="mt-1 text-xs text-text-secondary">Módulos com a mesma seção aparecem no mesmo carrossel. Em branco = "Aulas" (nível) ou "Atividades Extras".</p>
                @error('secao') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium mb-1 text-text-primary">Capa (JPG, PNG ou WEBP, vertical 3:4 fica melhor)</label>
                <input type="file" wire:model="capa" accept="image/jpeg,image/png,image/webp" class="w-full text-sm text-text-secondary">
                <div wire:loading wire:target="capa" class="text-xs text-text-secondary mt-1">Enviando...</div>
                @error('capa') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
                @if ($capaAtual && ! $capa)
                    <img src="{{ asset('storage/'.$capaAtual) }}" alt="Capa atual" class="mt-2 h-24 rounded-md object-cover">
                    <p class="mt-1 text-xs text-text-secondary">Deixe em branco pra manter a capa atual.</p>
                @endif
            </div>

            <div>
                <label class="block text-sm font-medium mb-1 text-text-primary">Ordem</label>
                <input type="number" wire:model="moduloOrdem" class="w-full rounded-md bg-surface border-surface-border text-text-primary">
            </div>

            <div class="flex justify-end gap-3 pt-2">
                <x-button variant="secondary" type="button" @click="open = false">Cancelar</x-button>
                <x-button type="submit">Salvar</x-button>
            </div>
        </form>
    </x-modal>

    <x-modal name="conteudo-form" title="Conteúdo">
        <form wire:submit="saveConteudo" class="space-y-4">
            <div>
                <label class="block text-sm font-medium mb-1 text-text-primary">Título</label>
                <input type="text" wire:model="titulo" class="w-full rounded-md bg-surface border-surface-border text-text-primary">
                @error('titulo') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium mb-1 text-text-primary">Tipo</label>
                <select wire:model.live="tipo" class="w-full rounded-md bg-surface border-surface-border text-text-primary">
                    <option value="video">Vídeo (YouTube/Vimeo)</option>
                    <option value="video_curto">Vídeo curto (upload, 30s a 1min)</option>
                    <option value="pdf">PDF</option>
                    <option value="texto">Texto</option>
                    <option value="exercicio">Exercício</option>
                    <option value="link">Link</option>
                </select>
            </div>

            @if ($tipo === 'texto')
                <div>
                    <label class="block text-sm font-medium mb-1 text-text-primary">Corpo (texto livre)</label>
                    <textarea wire:model="corpo" rows="4" class="w-full rounded-md bg-surface border-surface-border text-text-primary"></textarea>
                    @error('corpo') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
                </div>
            @endif

            @if ($tipo === 'video')
                <div>
                    <label class="block text-sm font-medium mb-1 text-text-primary">Link do YouTube ou Vimeo</label>
                    <input type="text" wire:model="urlExterna" placeholder="https://youtube.com/watch?v=..." class="w-full rounded-md bg-surface border-surface-border text-text-primary">
                    @error('urlExterna') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
                </div>
            @endif

            @if ($tipo === 'link')
                <div>
                    <label class="block text-sm font-medium mb-1 text-text-primary">URL externa</label>
                    <input type="text" wire:model="urlExterna" placeholder="https://..." class="w-full rounded-md bg-surface border-surface-border text-text-primary">
                    @error('urlExterna') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
                </div>
            @endif

            @if ($tipo === 'video_curto')
                <div>
                    <label class="block text-sm font-medium mb-1 text-text-primary">Arquivo de vídeo (MP4, WEBM ou MOV — até 50MB)</label>
                    <input type="file" wire:model="arquivo" accept="video/mp4,video/webm,video/quicktime" class="w-full text-sm text-text-secondary">
                    <div wire:loading wire:target="arquivo" class="text-xs text-text-secondary mt-1">Enviando...</div>
                    @error('arquivo') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
                    <p class="mt-1 text-xs text-text-secondary">Pensado pra vídeos de 30 segundos a 1 minuto (formato vertical funciona bem no celular). O limite é de tamanho, não de duração.</p>
                    @if ($conteudoId && ! $arquivo)
                        <p class="mt-1 text-xs text-text-secondary">Deixe em branco pra manter o vídeo atual.</p>
                    @endif
                </div>
            @endif

            @if ($tipo === 'pdf')
                <div>
                    <label class="block text-sm font-medium mb-1 text-text-primary">Arquivo PDF</label>
                    <input type="file" wire:model="arquivo" accept="application/pdf" class="w-full text-sm text-text-secondary">
                    <div wire:loading wire:target="arquivo" class="text-xs text-text-secondary mt-1">Enviando...</div>
                    @error('arquivo') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
                    @if ($conteudoId && ! $arquivo)
                        <p class="mt-1 text-xs text-text-secondary">Deixe em branco pra manter o arquivo atual.</p>
                    @endif
                </div>
            @endif

            @if ($tipo === 'exercicio')
                <div>
                    <label class="block text-sm font-medium mb-1 text-text-primary">Tipo de exercício</label>
                    <select wire:model.live="exercicioSubtipo" class="w-full rounded-md bg-surface border-surface-border text-text-primary">
                        <option value="anexo">Anexo (arquivo)</option>
                        <option value="quiz">Quiz interativo</option>
                    </select>
                    @error('exercicioSubtipo') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
                </div>

                @if ($exercicioSubtipo === 'anexo')
                    <div>
                        <label class="block text-sm font-medium mb-1 text-text-primary">Arquivo (PDF, DOC ou DOCX)</label>
                        <input type="file" wire:model="arquivo" class="w-full text-sm text-text-secondary">
                        <div wire:loading wire:target="arquivo" class="text-xs text-text-secondary mt-1">Enviando...</div>
                        @error('arquivo') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
                        @if ($conteudoId && ! $arquivo)
                            <p class="mt-1 text-xs text-text-secondary">Deixe em branco pra manter o arquivo atual.</p>
                        @endif
                    </div>
                @endif

                @if ($exercicioSubtipo === 'quiz')
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <label class="block text-sm font-medium text-text-primary">Perguntas</label>
                            <button type="button" wire:click="adicionarPergunta" class="text-primary hover:underline text-sm">+ Pergunta</button>
                        </div>
                        @error('perguntas') <p class="text-sm text-danger">{{ $message }}</p> @enderror

                        @foreach ($perguntas as $i => $pergunta)
                            <div class="rounded-lg border border-surface-border p-3 space-y-3">
                                <div class="flex items-start gap-2">
                                    <div class="flex-1">
                                        <input
                                            type="text"
                                            wire:model="perguntas.{{ $i }}.enunciado"
                                            placeholder="Enunciado da pergunta"
                                            class="w-full rounded-md bg-surface border-surface-border text-text-primary text-sm"
                                        >
                                        @error("perguntas.{$i}.enunciado") <p class="mt-1 text-xs text-danger">{{ $message }}</p> @enderror
                                    </div>
                                    <button type="button" wire:click="removerPergunta({{ $i }})" class="text-danger hover:underline text-xs mt-2">Remover</button>
                                </div>

                                <div class="pl-3 space-y-2">
                                    @error("perguntas.{$i}.opcoes") <p class="text-xs text-danger">{{ $message }}</p> @enderror
                                    @foreach ($pergunta['opcoes'] as $j => $opcao)
                                        <div class="flex items-center gap-2">
                                            <input type="checkbox" wire:model="perguntas.{{ $i }}.opcoes.{{ $j }}.correta" class="rounded bg-surface border-surface-border" title="Opção correta">
                                            <input
                                                type="text"
                                                wire:model="perguntas.{{ $i }}.opcoes.{{ $j }}.texto"
                                                placeholder="Texto da opção"
                                                class="flex-1 rounded-md bg-surface border-surface-border text-text-primary text-sm"
                                            >
                                            <button type="button" wire:click="removerOpcao({{ $i }}, {{ $j }})" class="text-danger hover:underline text-xs">Remover</button>
                                        </div>
                                        @error("perguntas.{$i}.opcoes.{$j}.texto") <p class="text-xs text-danger">{{ $message }}</p> @enderror
                                    @endforeach

                                    <button type="button" wire:click="adicionarOpcao({{ $i }})" class="text-primary hover:underline text-xs">+ Opção</button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            @endif

            <div>
                <label class="block text-sm font-medium mb-1 text-text-primary">Ordem</label>
                <input type="number" wire:model="conteudoOrdem" class="w-full rounded-md bg-surface border-surface-border text-text-primary">
            </div>

            <div>
                <label class="block text-sm font-medium mb-1 text-text-primary">Liberar após quantos dias da matrícula</label>
                <input type="number" wire:model="diasLiberacao" min="0" class="w-full rounded-md bg-surface border-surface-border text-text-primary">
                <p class="mt-1 text-xs text-text-secondary">0 = liberado imediatamente após a matrícula.</p>
                @error('diasLiberacao') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
            </div>

            <label class="flex items-center gap-2 text-sm text-text-secondary">
                <input type="checkbox" wire:model="bloqueado" class="rounded bg-surface border-surface-border">
                Bloqueado manualmente (força bloqueio mesmo se o prazo já passou)
            </label>

            <div class="flex justify-end gap-3 pt-2">
                <x-button variant="secondary" type="button" @click="open = false">Cancelar</x-button>
                <x-button type="submit">Salvar</x-button>
            </div>
        </form>
    </x-modal>
</div>
