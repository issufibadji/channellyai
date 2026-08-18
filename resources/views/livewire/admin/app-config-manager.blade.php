<div>
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-semibold text-text-primary">Configurações da Aplicação</h1>
        <x-button wire:click="create">+ Nova Configuração</x-button>
    </div>

    <x-table :headers="['Chave', 'Valor', 'Descrição', 'Mídia', 'Obrigatório', 'Ações']">
        @foreach ($configs as $config)
            <tr>
                <td class="px-4 py-3 font-mono text-sm">{{ $config->key }}</td>
                <td class="px-4 py-3 text-text-secondary">{{ $config->value ?? '—' }}</td>
                <td class="px-4 py-3 text-text-secondary">{{ $config->description ?? '—' }}</td>
                <td class="px-4 py-3">
                    @if ($config->media_path)
                        <img src="{{ asset('storage/'.$config->media_path) }}" class="h-8 w-8 rounded object-cover" alt="{{ $config->key }}">
                    @else
                        <span class="text-text-secondary">—</span>
                    @endif
                </td>
                <td class="px-4 py-3">
                    <x-badge :variant="$config->required ? 'warning' : 'default'">
                        {{ $config->required ? 'Sim' : 'Não' }}
                    </x-badge>
                </td>
                <td class="px-4 py-3 text-right whitespace-nowrap space-x-3">
                    <button wire:click="edit({{ $config->id }})" class="text-primary hover:underline text-sm">Editar</button>
                    <button
                        wire:click="delete({{ $config->id }})"
                        wire:confirm="Remover esta configuração?"
                        class="text-danger hover:underline text-sm"
                    >
                        Excluir
                    </button>
                </td>
            </tr>
        @endforeach
    </x-table>

    <x-modal name="config-form" title="Configuração">
        <form wire:submit="save" class="space-y-4">
            <div>
                <label class="block text-sm font-medium mb-1 text-text-primary">Chave</label>
                <input type="text" wire:model="key" class="w-full rounded-md bg-surface border-surface-border text-text-primary font-mono text-sm">
                @error('key') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium mb-1 text-text-primary">Valor</label>
                <input type="text" wire:model="value" class="w-full rounded-md bg-surface border-surface-border text-text-primary">
                @error('value') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium mb-1 text-text-primary">Descrição</label>
                <textarea wire:model="description" rows="3" class="w-full rounded-md bg-surface border-surface-border text-text-primary"></textarea>
                @error('description') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium mb-1 text-text-primary">Mídia</label>
                <input type="file" wire:model="media" class="w-full text-sm text-text-secondary">
                <div wire:loading wire:target="media" class="text-xs text-text-secondary mt-1">Enviando...</div>
                @error('media') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
                <p class="mt-1 text-xs text-text-secondary">Valor e Mídia não se sobrepõem — use um ou outro conforme o tipo da configuração.</p>
            </div>

            <label class="flex items-center gap-2 text-sm text-text-secondary">
                <input type="checkbox" wire:model="required" class="rounded bg-surface border-surface-border">
                Obrigatório
            </label>

            <div class="flex justify-end gap-3 pt-2">
                <x-button variant="secondary" type="button" @click="open = false">Cancelar</x-button>
                <x-button type="submit">Salvar</x-button>
            </div>
        </form>
    </x-modal>
</div>
