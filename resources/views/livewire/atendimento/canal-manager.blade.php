<div>
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-semibold text-text-primary">Canais</h1>
        <x-button wire:click="create">+ Novo canal</x-button>
    </div>

    <x-table :headers="['Nome', 'Tipo', 'Status', 'Ações']">
        @forelse ($canais as $canal)
            <tr>
                <td class="px-4 py-3">{{ $canal->nome }}</td>
                <td class="px-4 py-3 text-text-secondary">{{ \App\Models\Atendimento\Canal::TIPOS[$canal->tipo] ?? $canal->tipo }}</td>
                <td class="px-4 py-3">
                    <x-badge :variant="$canal->ativo ? 'success' : 'default'">{{ $canal->ativo ? 'Ativo' : 'Inativo' }}</x-badge>
                </td>
                <td class="px-4 py-3 text-right whitespace-nowrap space-x-3">
                    <button wire:click="edit({{ $canal->id }})" class="text-primary hover:underline text-sm">Editar</button>
                    <button wire:click="toggleAtivo({{ $canal->id }})" class="text-text-secondary hover:underline text-sm">
                        {{ $canal->ativo ? 'Desativar' : 'Ativar' }}
                    </button>
                    <button
                        wire:click="delete({{ $canal->id }})"
                        wire:confirm="Remover este canal?"
                        class="text-danger hover:underline text-sm"
                    >
                        Excluir
                    </button>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="4" class="px-4 py-6 text-center text-text-secondary">Nenhum canal configurado.</td>
            </tr>
        @endforelse
    </x-table>

    <x-modal name="canal-form" title="Canal">
        <form wire:submit="save" class="space-y-4">
            <div>
                <label class="block text-sm font-medium mb-1 text-text-primary">Nome</label>
                <input type="text" wire:model="nome" placeholder="ex.: WhatsApp — Comercial" class="w-full rounded-md bg-surface border-surface-border text-text-primary">
                @error('nome') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium mb-1 text-text-primary">Tipo</label>
                <select wire:model="tipo" class="w-full rounded-md bg-surface border-surface-border text-text-primary">
                    @foreach (\App\Models\Atendimento\Canal::TIPOS as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
                @error('tipo') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
            </div>

            <label class="flex items-center gap-2 text-sm text-text-secondary">
                <input type="checkbox" wire:model="ativo" class="rounded bg-surface border-surface-border">
                Canal ativo
            </label>

            <div class="flex justify-end gap-3 pt-2">
                <x-button variant="secondary" type="button" @click="open = false">Cancelar</x-button>
                <x-button type="submit">Salvar</x-button>
            </div>
        </form>
    </x-modal>
</div>
