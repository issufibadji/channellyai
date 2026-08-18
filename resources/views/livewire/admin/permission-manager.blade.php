<div>
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-semibold text-text-primary">Permissões</h1>
        <x-button wire:click="create">+ Permissão</x-button>
    </div>

    <x-table :headers="['Nome', 'Tag', 'Ações']">
        @foreach ($permissions as $permission)
            <tr>
                <td class="px-4 py-3">{{ $permission->name }}</td>
                <td class="px-4 py-3 text-text-secondary">{{ $permission->tag ?? '—' }}</td>
                <td class="px-4 py-3 text-right whitespace-nowrap space-x-3">
                    <button wire:click="edit({{ $permission->id }})" class="text-primary hover:underline text-sm">Editar</button>
                    <button
                        wire:click="delete({{ $permission->id }})"
                        wire:confirm="Remover esta permissão?"
                        class="text-danger hover:underline text-sm"
                    >
                        Excluir
                    </button>
                </td>
            </tr>
        @endforeach
    </x-table>

    <x-modal name="permission-form" title="Permissão">
        <form wire:submit="save" class="space-y-4">
            <div>
                <label class="block text-sm font-medium mb-1 text-text-primary">Nome</label>
                <input type="text" wire:model="name" class="w-full rounded-md bg-surface border-surface-border text-text-primary">
                @error('name') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium mb-1 text-text-primary">Tag</label>
                <input type="text" wire:model="tag" class="w-full rounded-md bg-surface border-surface-border text-text-primary">
                @error('tag') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
            </div>

            <div class="flex justify-end gap-3 pt-2">
                <x-button variant="secondary" type="button" @click="open = false">Cancelar</x-button>
                <x-button type="submit">Salvar</x-button>
            </div>
        </form>
    </x-modal>
</div>
