<div x-data="{ tab: 'ajudas' }">
    <h1 class="text-2xl font-semibold text-text-primary mb-6">Gerenciar Ajuda</h1>

    <div class="flex gap-2 mb-6 border-b border-surface-border">
        <button
            type="button"
            @click="tab = 'ajudas'"
            :class="tab === 'ajudas' ? 'border-primary text-text-primary' : 'border-transparent text-text-secondary'"
            class="px-3 py-2 text-sm font-medium border-b-2 -mb-px"
        >
            Textos por Perfil
        </button>
        <button
            type="button"
            @click="tab = 'faqs'"
            :class="tab === 'faqs' ? 'border-primary text-text-primary' : 'border-transparent text-text-secondary'"
            class="px-3 py-2 text-sm font-medium border-b-2 -mb-px"
        >
            FAQ do Aluno
        </button>
    </div>

    <div x-show="tab === 'ajudas'">
        <x-table :headers="['Perfil', 'Conteúdo', 'Ações']">
            @foreach ($ajudas as $ajuda)
                <tr>
                    <td class="px-4 py-3 font-medium text-text-primary capitalize">{{ $ajuda->role }}</td>
                    <td class="px-4 py-3 text-text-secondary">{{ \Illuminate\Support\Str::limit($ajuda->conteudo, 80) }}</td>
                    <td class="px-4 py-3 text-right whitespace-nowrap">
                        <button wire:click="editAjuda({{ $ajuda->id }})" class="text-primary hover:underline text-sm">Editar</button>
                    </td>
                </tr>
            @endforeach
        </x-table>
    </div>

    <div x-show="tab === 'faqs'" x-cloak>
        <div class="flex justify-end mb-4">
            <x-button wire:click="createFaq">+ Nova Pergunta</x-button>
        </div>

        <x-table :headers="['Ordem', 'Ícone', 'Pergunta', 'Ativo', 'Ações']">
            @foreach ($faqs as $faq)
                <tr>
                    <td class="px-4 py-3 text-text-secondary whitespace-nowrap">
                        <div class="flex items-center gap-1">
                            <button wire:click="moveUp({{ $faq->id }})" class="text-text-secondary hover:text-text-primary" title="Mover para cima">
                                <x-heroicon-o-chevron-up class="w-4 h-4" />
                            </button>
                            <button wire:click="moveDown({{ $faq->id }})" class="text-text-secondary hover:text-text-primary" title="Mover para baixo">
                                <x-heroicon-o-chevron-down class="w-4 h-4" />
                            </button>
                            <span>{{ $faq->ordem }}</span>
                        </div>
                    </td>
                    <td class="px-4 py-3">
                        @if ($faq->icone)
                            <x-icon :name="'heroicon-o-'.$faq->icone" class="w-4 h-4 text-text-secondary" />
                        @else
                            —
                        @endif
                    </td>
                    <td class="px-4 py-3 text-text-primary">{{ $faq->pergunta }}</td>
                    <td class="px-4 py-3">
                        <button wire:click="toggleAtivo({{ $faq->id }})">
                            <x-badge :variant="$faq->ativo ? 'success' : 'default'">
                                {{ $faq->ativo ? 'Ativo' : 'Inativo' }}
                            </x-badge>
                        </button>
                    </td>
                    <td class="px-4 py-3 text-right whitespace-nowrap space-x-3">
                        <button wire:click="editFaq({{ $faq->id }})" class="text-primary hover:underline text-sm">Editar</button>
                        <button
                            wire:click="deleteFaq({{ $faq->id }})"
                            wire:confirm="Remover esta pergunta?"
                            class="text-danger hover:underline text-sm"
                        >
                            Excluir
                        </button>
                    </td>
                </tr>
            @endforeach
        </x-table>
    </div>

    <x-modal name="ajuda-form" title="Texto de Ajuda">
        <form wire:submit="saveAjuda" class="space-y-4">
            <div>
                <label class="block text-sm font-medium mb-1 text-text-primary capitalize">{{ $ajudaRole }}</label>
                <textarea wire:model="ajudaConteudo" rows="6" class="w-full rounded-md bg-surface border-surface-border text-text-primary"></textarea>
                @error('ajudaConteudo') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
            </div>

            <div class="flex justify-end gap-3 pt-2">
                <x-button variant="secondary" type="button" @click="open = false">Cancelar</x-button>
                <x-button type="submit">Salvar</x-button>
            </div>
        </form>
    </x-modal>

    <x-modal name="faq-form" title="Pergunta Frequente">
        <form wire:submit="saveFaq" class="space-y-4">
            <div>
                <label class="block text-sm font-medium mb-1 text-text-primary">Ícone (heroicon)</label>
                <input type="text" wire:model="icone" placeholder="ex.: play, chart-bar, wifi" class="w-full rounded-md bg-surface border-surface-border text-text-primary font-mono text-sm">
                @error('icone') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium mb-1 text-text-primary">Pergunta</label>
                <input type="text" wire:model="pergunta" class="w-full rounded-md bg-surface border-surface-border text-text-primary">
                @error('pergunta') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium mb-1 text-text-primary">Resposta</label>
                <textarea wire:model="resposta" rows="4" class="w-full rounded-md bg-surface border-surface-border text-text-primary"></textarea>
                @error('resposta') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium mb-1 text-text-primary">Ordem</label>
                <input type="number" wire:model="ordem" class="w-full rounded-md bg-surface border-surface-border text-text-primary">
                @error('ordem') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
            </div>

            <label class="flex items-center gap-2 text-sm text-text-secondary">
                <input type="checkbox" wire:model="ativo" class="rounded bg-surface border-surface-border">
                Ativo
            </label>

            <div class="flex justify-end gap-3 pt-2">
                <x-button variant="secondary" type="button" @click="open = false">Cancelar</x-button>
                <x-button type="submit">Salvar</x-button>
            </div>
        </form>
    </x-modal>
</div>
