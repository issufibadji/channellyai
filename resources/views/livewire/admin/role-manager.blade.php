<div>
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-semibold text-text-primary">Papéis</h1>
        <x-button wire:click="create">+ Papel</x-button>
    </div>

    <x-table :headers="['Nome', 'Permissões', 'Ações']">
        @foreach ($roles as $role)
            <tr>
                <td class="px-4 py-3">{{ $role->name }}</td>
                <td class="px-4 py-3">
                    @forelse ($role->permissions as $permission)
                        <x-badge variant="primary">{{ $permission->name }}</x-badge>
                    @empty
                        <span class="text-text-secondary">—</span>
                    @endforelse
                </td>
                <td class="px-4 py-3 text-right whitespace-nowrap space-x-3">
                    <button wire:click="edit({{ $role->id }})" class="text-primary hover:underline text-sm">Editar</button>
                    <button
                        wire:click="delete({{ $role->id }})"
                        wire:confirm="Remover este papel?"
                        class="text-danger hover:underline text-sm"
                    >
                        Excluir
                    </button>
                </td>
            </tr>
        @endforeach
    </x-table>

    <x-modal name="role-form" title="Papel">
        <form wire:submit="save" class="space-y-4">
            <div>
                <label class="block text-sm font-medium mb-1 text-text-primary">Nome do papel</label>
                <input type="text" wire:model="name" class="w-full rounded-md bg-surface border-surface-border text-text-primary">
                @error('name') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium mb-1 text-text-primary">Permissões</label>
                <div class="max-h-56 overflow-y-auto space-y-1 border border-surface-border rounded-md p-3">
                    @foreach ($allPermissions as $permission)
                        <label class="flex items-center gap-2 text-sm text-text-secondary">
                            <input type="checkbox" wire:model="permissions" value="{{ $permission->name }}" class="rounded bg-surface border-surface-border">
                            {{ $permission->name }}
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="flex justify-end gap-3 pt-2">
                <x-button variant="secondary" type="button" @click="open = false">Cancelar</x-button>
                <x-button type="submit">Salvar</x-button>
            </div>
        </form>
    </x-modal>
</div>
