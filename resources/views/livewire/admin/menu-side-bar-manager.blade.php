<div>
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-semibold text-text-primary">Menu</h1>
        <x-button wire:click="create">Novo item</x-button>
    </div>

    <x-table :headers="['Label', 'Seção', 'Rota', 'Permissão', 'Ordem', 'Ações']">
        @foreach ($items as $item)
            <tr>
                <td class="px-4 py-3">{{ $item->label }}</td>
                <td class="px-4 py-3 text-text-secondary">{{ $item->group ?? '—' }}</td>
                <td class="px-4 py-3 text-text-secondary">{{ $item->route_name }}</td>
                <td class="px-4 py-3">
                    @if ($item->permission)
                        <x-badge variant="primary">{{ $item->permission }}</x-badge>
                    @else
                        <span class="text-text-secondary">—</span>
                    @endif
                </td>
                <td class="px-4 py-3">{{ $item->order }}</td>
                <td class="px-4 py-3 text-right whitespace-nowrap">
                    <button wire:click="edit({{ $item->id }})" class="text-primary hover:underline text-sm">Editar</button>
                    <button
                        wire:click="delete({{ $item->id }})"
                        wire:confirm="Remover este item de menu?"
                        class="text-danger hover:underline text-sm ml-3"
                    >
                        Excluir
                    </button>
                </td>
            </tr>

            @foreach ($item->children as $child)
                <tr>
                    <td class="px-4 py-3 pl-10 text-text-secondary">{{ $child->label }}</td>
                    <td class="px-4 py-3 text-text-secondary">{{ $child->group ?? '—' }}</td>
                    <td class="px-4 py-3 text-text-secondary">{{ $child->route_name }}</td>
                    <td class="px-4 py-3">
                        @if ($child->permission)
                            <x-badge variant="primary">{{ $child->permission }}</x-badge>
                        @else
                            <span class="text-text-secondary">—</span>
                        @endif
                    </td>
                    <td class="px-4 py-3">{{ $child->order }}</td>
                    <td class="px-4 py-3 text-right whitespace-nowrap">
                        <button wire:click="edit({{ $child->id }})" class="text-primary hover:underline text-sm">Editar</button>
                        <button
                            wire:click="delete({{ $child->id }})"
                            wire:confirm="Remover este item de menu?"
                            class="text-danger hover:underline text-sm ml-3"
                        >
                            Excluir
                        </button>
                    </td>
                </tr>
            @endforeach
        @endforeach
    </x-table>

    <x-modal name="menu-form" title="Item de menu">
        <form wire:submit="save" class="space-y-4">
            <div>
                <label class="block text-sm font-medium mb-1 text-text-primary">Label</label>
                <input type="text" wire:model="label" class="w-full rounded-md bg-surface border-surface-border text-text-primary">
                @error('label') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium mb-1 text-text-primary">Seção (agrupamento visual na sidebar)</label>
                <input type="text" wire:model="group" list="menu-groups" placeholder="ex.: Atendimento, Administração do Sistema" class="w-full rounded-md bg-surface border-surface-border text-text-primary">
                <datalist id="menu-groups">
                    @foreach ($existingGroups as $existingGroup)
                        <option value="{{ $existingGroup }}"></option>
                    @endforeach
                </datalist>
                @error('group') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
                <p class="mt-1 text-xs text-text-secondary">Itens com a mesma seção ficam agrupados sob um cabeçalho na sidebar. Deixe em branco para não agrupar.</p>
            </div>

            <div>
                <label class="block text-sm font-medium mb-1 text-text-primary">Ícone (nome do heroicon)</label>
                <input type="text" wire:model="icon" placeholder="ex.: home, users, cog-6-tooth" class="w-full rounded-md bg-surface border-surface-border text-text-primary">
                @error('icon') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium mb-1 text-text-primary">Nome da rota</label>
                <input type="text" wire:model="routeName" placeholder="ex.: dashboard" class="w-full rounded-md bg-surface border-surface-border text-text-primary">
                @error('routeName') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium mb-1 text-text-primary">Item pai (submenu dentro de outro item)</label>
                <select wire:model="parentId" class="w-full rounded-md bg-surface border-surface-border text-text-primary">
                    <option value="">— Nenhum (item raiz) —</option>
                    @foreach ($parentOptions as $option)
                        <option value="{{ $option->id }}">{{ $option->label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium mb-1 text-text-primary">Ordem</label>
                <input type="number" wire:model="order" class="w-full rounded-md bg-surface border-surface-border text-text-primary">
                @error('order') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
            </div>

            <div class="flex justify-end gap-3 pt-2">
                <x-button variant="secondary" type="button" @click="open = false">Cancelar</x-button>
                <x-button type="submit">Salvar</x-button>
            </div>
        </form>
    </x-modal>
</div>
