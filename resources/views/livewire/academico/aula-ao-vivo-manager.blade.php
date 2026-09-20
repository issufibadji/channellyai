<div>
    <div class="flex items-center justify-between mb-6 flex-wrap gap-3">
        <div>
            <a href="{{ route('academico.minhas-turmas.index') }}" class="text-sm text-primary hover:underline">&larr; Minhas Turmas</a>
            <h1 class="text-2xl font-semibold text-text-primary mt-1">Aulas ao vivo — {{ $turma->nome }}</h1>
            <p class="text-sm text-text-secondary">A sala abre dentro do sistema. Ao iniciar, os alunos da turma são avisados.</p>
        </div>
        <x-button wire:click="create">+ Agendar aula ao vivo</x-button>
    </div>

    <div class="space-y-3">
        @forelse ($aulas as $aula)
            <x-card>
                <div class="flex items-start justify-between gap-4 flex-wrap">
                    <div class="min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <h2 class="text-lg font-semibold text-text-primary">{{ $aula->titulo }}</h2>
                            @if ($aula->estaAoVivo())
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-red-500/15 text-red-500 text-xs font-semibold">
                                    <span class="w-1.5 h-1.5 rounded-full bg-red-500 animate-pulse"></span> AO VIVO
                                </span>
                            @elseif ($aula->estaEncerrada())
                                <x-badge>Encerrada</x-badge>
                            @else
                                <x-badge variant="primary">Agendada</x-badge>
                            @endif
                        </div>
                        <p class="text-sm text-text-secondary mt-1">
                            {{ $aula->inicio_em->format('d/m/Y \à\s H:i') }} · {{ $aula->duracao_minutos }} min
                        </p>
                        @if ($aula->descricao)
                            <p class="text-sm text-text-secondary mt-2 whitespace-pre-line">{{ $aula->descricao }}</p>
                        @endif
                        @if ($aula->conteudo)
                            <p class="text-sm text-success mt-2">
                                Gravação publicada em
                                <a href="{{ route('academico.turmas.conteudo', $turma) }}" class="underline">{{ $aula->conteudo->modulo->nome }}</a>
                            </p>
                        @endif
                    </div>

                    <div class="flex items-center gap-3 text-sm whitespace-nowrap">
                        @if ($aula->estaEncerrada())
                            <button wire:click="abrirGravacao({{ $aula->id }})" class="px-4 py-2 rounded-full font-medium bg-linear-to-r from-primary to-accent text-white shadow-md shadow-primary/25">
                                {{ $aula->conteudo ? 'Editar gravação' : 'Publicar gravação' }}
                            </button>
                        @else
                            @if ($aula->estaAoVivo())
                                <a href="{{ route('academico.ao-vivo.sala', $aula) }}" class="px-4 py-2 rounded-full font-medium bg-linear-to-r from-primary to-accent text-white shadow-md shadow-primary/25">Entrar na sala</a>
                                <button wire:click="encerrar({{ $aula->id }})" wire:confirm="Encerrar a aula pra todos?" class="text-danger hover:underline">Encerrar</button>
                            @else
                                <button wire:click="iniciar({{ $aula->id }})" class="px-4 py-2 rounded-full font-medium bg-linear-to-r from-primary to-accent text-white shadow-md shadow-primary/25">Iniciar agora</button>
                                <button wire:click="edit({{ $aula->id }})" class="text-primary hover:underline">Editar</button>
                            @endif
                        @endif
                        <button wire:click="delete({{ $aula->id }})" wire:confirm="Remover esta aula ao vivo?" class="text-danger hover:underline">Excluir</button>
                    </div>
                </div>
            </x-card>
        @empty
            <x-card><p class="text-sm text-text-secondary">Nenhuma aula ao vivo agendada ainda.</p></x-card>
        @endforelse
    </div>

    <x-modal name="aula-ao-vivo-form" title="Aula ao vivo">
        <form wire:submit="save" class="space-y-4">
            <div>
                <label class="block text-sm font-medium mb-1 text-text-primary">Título</label>
                <input type="text" wire:model="titulo" class="w-full rounded-md bg-surface border-surface-border text-text-primary">
                @error('titulo') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium mb-1 text-text-primary">Descrição (opcional)</label>
                <textarea wire:model="descricao" rows="3" class="w-full rounded-md bg-surface border-surface-border text-text-primary"></textarea>
                @error('descricao') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-medium mb-1 text-text-primary">Data e hora</label>
                    <input type="datetime-local" wire:model="inicioEm" class="w-full rounded-md bg-surface border-surface-border text-text-primary">
                    @error('inicioEm') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1 text-text-primary">Duração (min)</label>
                    <input type="number" wire:model="duracaoMinutos" min="15" max="480" class="w-full rounded-md bg-surface border-surface-border text-text-primary">
                    @error('duracaoMinutos') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="flex justify-end gap-3 pt-2">
                <x-button variant="secondary" type="button" @click="open = false">Cancelar</x-button>
                <x-button type="submit">Salvar</x-button>
            </div>
        </form>
    </x-modal>

    <x-modal name="gravacao-form" title="Publicar gravação da aula">
        <form wire:submit="salvarGravacao" class="space-y-4">
            <div>
                <label class="block text-sm font-medium mb-1 text-text-primary">Link da gravação (Google Drive, YouTube ou Vimeo)</label>
                <input type="text" wire:model="gravacaoUrl" placeholder="https://drive.google.com/file/d/.../view" class="w-full rounded-md bg-surface border-surface-border text-text-primary">
                @error('gravacaoUrl') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
                <p class="mt-1 text-xs text-text-secondary">No Google Drive, compartilhe como "Qualquer pessoa com o link". A gravação vira um vídeo da turma, com o mesmo player das outras aulas.</p>
            </div>

            <div>
                <label class="block text-sm font-medium mb-1 text-text-primary">Onde publicar</label>
                <select wire:model="moduloDestino" class="w-full rounded-md bg-surface border-surface-border text-text-primary">
                    <option value="novo">Módulo "Aulas Gravadas" (não conta no progresso do curso)</option>
                    @foreach ($modulosDaTurma as $modulo)
                        <option value="{{ $modulo->id }}">{{ $modulo->nome }}{{ $modulo->nivel ? ' — '.$modulo->nivel : '' }}</option>
                    @endforeach
                </select>
                @error('moduloDestino') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
            </div>

            <div class="flex justify-end gap-3 pt-2">
                <x-button variant="secondary" type="button" @click="open = false">Cancelar</x-button>
                <x-button type="submit">Publicar</x-button>
            </div>
        </form>
    </x-modal>
</div>
