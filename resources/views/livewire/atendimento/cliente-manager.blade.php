<div>
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-semibold text-text-primary">Clientes</h1>
        <x-button wire:click="create">+ Novo cliente</x-button>
    </div>

    <div class="mb-4">
        <input
            type="text"
            wire:model.live.debounce.300ms="search"
            placeholder="Buscar nome, e-mail ou telefone..."
            class="w-full max-w-sm rounded-md bg-surface-card border-surface-border text-text-primary text-sm"
        >
    </div>

    <x-table :headers="['Nome', 'E-mail', 'Telefone', 'Documento', 'Atendimentos', 'Ações']">
        @forelse ($clientes as $cliente)
            <tr>
                <td class="px-4 py-3">{{ $cliente->nome }}</td>
                <td class="px-4 py-3 text-text-secondary">{{ $cliente->email ?? '—' }}</td>
                <td class="px-4 py-3 text-text-secondary">{{ $cliente->telefone ?? '—' }}</td>
                <td class="px-4 py-3 text-text-secondary">{{ $cliente->documento ?? '—' }}</td>
                <td class="px-4 py-3">{{ $cliente->atendimentos()->count() }}</td>
                <td class="px-4 py-3 text-right whitespace-nowrap space-x-3">
                    <button wire:click="edit({{ $cliente->id }})" class="text-primary hover:underline text-sm">Editar</button>
                    <button
                        wire:click="delete({{ $cliente->id }})"
                        wire:confirm="Remover este cliente?"
                        class="text-danger hover:underline text-sm"
                    >
                        Excluir
                    </button>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="6" class="px-4 py-6 text-center text-text-secondary">Nenhum cliente cadastrado.</td>
            </tr>
        @endforelse
    </x-table>

    <div class="mt-4">
        {{ $clientes->links() }}
    </div>

    <x-modal name="cliente-form" title="Cliente">
        <form wire:submit="save" class="space-y-4">
            <div>
                <label class="block text-sm font-medium mb-1 text-text-primary">Nome</label>
                <input type="text" wire:model="nome" class="w-full rounded-md bg-surface border-surface-border text-text-primary">
                @error('nome') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium mb-1 text-text-primary">E-mail</label>
                    <input type="email" wire:model="email" class="w-full rounded-md bg-surface border-surface-border text-text-primary">
                    @error('email') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1 text-text-primary">Telefone</label>
                    <input type="text" wire:model="telefone" class="w-full rounded-md bg-surface border-surface-border text-text-primary">
                    @error('telefone') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium mb-1 text-text-primary">Documento</label>
                <input type="text" wire:model="documento" class="w-full rounded-md bg-surface border-surface-border text-text-primary">
                @error('documento') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium mb-1 text-text-primary">Notas</label>
                <textarea wire:model="notas" rows="3" class="w-full rounded-md bg-surface border-surface-border text-text-primary"></textarea>
                @error('notas') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
            </div>

            <div class="flex justify-end gap-3 pt-2">
                <x-button variant="secondary" type="button" @click="open = false">Cancelar</x-button>
                <x-button type="submit">Salvar</x-button>
            </div>
        </form>
    </x-modal>
</div>
