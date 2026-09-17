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
                        <span class="text-xs text-text-secondary">Nível {{ $modulo->nivel }} · ordem {{ $modulo->ordem }}</span>
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
                            <td class="px-4 py-3"><x-badge variant="primary">{{ $conteudo->tipo }}</x-badge></td>
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
                <label class="block text-sm font-medium mb-1 text-text-primary">Nível</label>
                <select wire:model="nivel" class="w-full rounded-md bg-surface border-surface-border text-text-primary">
                    @foreach (['A1', 'A2', 'B1', 'B2', 'C1', 'C2'] as $opcao)
                        <option value="{{ $opcao }}">{{ $opcao }}</option>
                    @endforeach
                </select>
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
                <select wire:model="tipo" class="w-full rounded-md bg-surface border-surface-border text-text-primary">
                    <option value="video">Vídeo</option>
                    <option value="pdf">PDF</option>
                    <option value="texto">Texto</option>
                    <option value="exercicio">Exercício</option>
                    <option value="link">Link</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium mb-1 text-text-primary">Corpo (texto livre)</label>
                <textarea wire:model="corpo" rows="3" class="w-full rounded-md bg-surface border-surface-border text-text-primary"></textarea>
            </div>

            <div>
                <label class="block text-sm font-medium mb-1 text-text-primary">URL externa (quando tipo = link)</label>
                <input type="text" wire:model="urlExterna" class="w-full rounded-md bg-surface border-surface-border text-text-primary">
                @error('urlExterna') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium mb-1 text-text-primary">Ordem</label>
                <input type="number" wire:model="conteudoOrdem" class="w-full rounded-md bg-surface border-surface-border text-text-primary">
            </div>

            <div class="flex justify-end gap-3 pt-2">
                <x-button variant="secondary" type="button" @click="open = false">Cancelar</x-button>
                <x-button type="submit">Salvar</x-button>
            </div>
        </form>
    </x-modal>
</div>
