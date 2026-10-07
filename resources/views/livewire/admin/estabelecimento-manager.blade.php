<div>
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-semibold text-text-primary">Estabelecimentos</h1>
        @can('manage-estabelecimentos')
            <x-button wire:click="create">+ Estabelecimento</x-button>
        @endcan
    </div>

    <div class="flex flex-wrap gap-3 mb-4">
        <input
            type="text"
            wire:model.live.debounce.300ms="search"
            placeholder="Buscar por nome..."
            class="rounded-md bg-surface-card border-surface-border text-text-primary text-sm"
        >
    </div>

    <x-table :headers="['Nome', 'Slug', 'Tipo', 'Status', 'Usuários', 'Ações']">
        @foreach ($estabelecimentos as $estabelecimento)
            <tr>
                <td class="px-4 py-3">{{ $estabelecimento->nome }}</td>
                <td class="px-4 py-3 text-text-secondary">{{ $estabelecimento->slug }}</td>
                <td class="px-4 py-3">{{ \App\Models\Estabelecimento::TIPOS_NEGOCIO[$estabelecimento->tipo_negocio] ?? $estabelecimento->tipo_negocio }}</td>
                <td class="px-4 py-3">
                    <x-badge :variant="$estabelecimento->ativo ? 'success' : 'danger'">
                        {{ $estabelecimento->ativo ? 'Ativo' : 'Inativo' }}
                    </x-badge>
                </td>
                <td class="px-4 py-3">{{ $estabelecimento->usuarios()->count() }}</td>
                <td class="px-4 py-3 text-right whitespace-nowrap space-x-3">
                    @can('manage-estabelecimentos')
                        <button wire:click="edit({{ $estabelecimento->id }})" class="text-primary hover:underline text-sm">Editar</button>
                        <button
                            wire:click="delete({{ $estabelecimento->id }})"
                            wire:confirm="Remover este estabelecimento?"
                            class="text-danger hover:underline text-sm"
                        >
                            Excluir
                        </button>
                    @endcan
                </td>
            </tr>
        @endforeach
    </x-table>

    <div class="mt-4">
        {{ $estabelecimentos->links() }}
    </div>

    <x-modal name="estabelecimento-form" title="Estabelecimento">
        <form wire:submit="save" class="space-y-4">
            <div>
                <label class="block text-sm font-medium mb-1 text-text-primary">Nome</label>
                <input type="text" wire:model="nome" class="w-full rounded-md bg-surface border-surface-border text-text-primary">
                @error('nome') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium mb-1 text-text-primary">
                    Slug {{ $estabelecimentoId ? '(não pode ser alterado)' : '' }}
                </label>
                <input
                    type="text"
                    wire:model="slug"
                    @if ($estabelecimentoId) disabled @endif
                    class="w-full rounded-md bg-surface border-surface-border text-text-primary disabled:opacity-50"
                    placeholder="ex: barbearia-do-ze"
                >
                @error('slug') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium mb-1 text-text-primary">Tipo de negócio</label>
                <select wire:model="tipoNegocio" class="w-full rounded-md bg-surface border-surface-border text-text-primary">
                    @foreach (\App\Models\Estabelecimento::TIPOS_NEGOCIO as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="flex items-center gap-2 text-sm text-text-secondary">
                    <input type="checkbox" wire:model="ativo" class="rounded bg-surface border-surface-border">
                    Ativo
                </label>
            </div>

            <div>
                <label class="block text-sm font-medium mb-1 text-text-primary">Usuários vinculados</label>
                <div class="space-y-1 max-h-48 overflow-y-auto">
                    @foreach ($allUsers as $user)
                        <label class="flex items-center gap-2 text-sm text-text-secondary">
                            <input type="checkbox" wire:model="usuarios" value="{{ $user->id }}" class="rounded bg-surface border-surface-border">
                            {{ $user->name }}
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
