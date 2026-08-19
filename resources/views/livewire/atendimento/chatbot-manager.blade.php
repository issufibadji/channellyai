<div>
    <div class="flex items-center justify-between mb-2">
        <h1 class="text-2xl font-semibold text-text-primary">IA e Chatbot</h1>
        <x-button wire:click="create">+ Nova regra</x-button>
    </div>
    <p class="text-sm text-text-secondary mb-6">
        Regras de resposta automática por palavra-chave. Quando nenhuma regra corresponde, o atendimento é transferido para o setor "Outros".
    </p>

    <x-table :headers="['Gatilho', 'Resposta', 'Transfere para', 'Ordem', 'Status', 'Ações']">
        @forelse ($regras as $regra)
            <tr>
                <td class="px-4 py-3 font-mono text-sm">{{ $regra->gatilho }}</td>
                <td class="px-4 py-3 text-text-secondary max-w-xs truncate">{{ $regra->resposta ?? '—' }}</td>
                <td class="px-4 py-3 text-text-secondary">{{ \App\Models\Atendimento\Atendimento::SETORES[$regra->setor_transferencia] ?? '—' }}</td>
                <td class="px-4 py-3">{{ $regra->order }}</td>
                <td class="px-4 py-3">
                    <x-badge :variant="$regra->ativo ? 'success' : 'default'">{{ $regra->ativo ? 'Ativa' : 'Inativa' }}</x-badge>
                </td>
                <td class="px-4 py-3 text-right whitespace-nowrap space-x-3">
                    <button wire:click="edit({{ $regra->id }})" class="text-primary hover:underline text-sm">Editar</button>
                    <button wire:click="toggleAtivo({{ $regra->id }})" class="text-text-secondary hover:underline text-sm">
                        {{ $regra->ativo ? 'Desativar' : 'Ativar' }}
                    </button>
                    <button
                        wire:click="delete({{ $regra->id }})"
                        wire:confirm="Remover esta regra?"
                        class="text-danger hover:underline text-sm"
                    >
                        Excluir
                    </button>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="6" class="px-4 py-6 text-center text-text-secondary">Nenhuma regra cadastrada.</td>
            </tr>
        @endforelse
    </x-table>

    <x-modal name="regra-form" title="Regra do chatbot">
        <form wire:submit="save" class="space-y-4">
            <div>
                <label class="block text-sm font-medium mb-1 text-text-primary">Gatilho (palavra-chave)</label>
                <input type="text" wire:model="gatilho" placeholder="ex.: boleto" class="w-full rounded-md bg-surface border-surface-border text-text-primary">
                @error('gatilho') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium mb-1 text-text-primary">Resposta automática</label>
                <textarea wire:model="resposta" rows="3" class="w-full rounded-md bg-surface border-surface-border text-text-primary"></textarea>
                @error('resposta') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium mb-1 text-text-primary">Transferir para o setor</label>
                    <select wire:model="setorTransferencia" class="w-full rounded-md bg-surface border-surface-border text-text-primary">
                        <option value="">— Não transferir —</option>
                        @foreach (\App\Models\Atendimento\Atendimento::SETORES as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('setorTransferencia') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1 text-text-primary">Ordem de avaliação</label>
                    <input type="number" wire:model="order" min="0" class="w-full rounded-md bg-surface border-surface-border text-text-primary">
                    @error('order') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
                </div>
            </div>

            <label class="flex items-center gap-2 text-sm text-text-secondary">
                <input type="checkbox" wire:model="ativo" class="rounded bg-surface border-surface-border">
                Regra ativa
            </label>

            <div class="flex justify-end gap-3 pt-2">
                <x-button variant="secondary" type="button" @click="open = false">Cancelar</x-button>
                <x-button type="submit">Salvar</x-button>
            </div>
        </form>
    </x-modal>
</div>
