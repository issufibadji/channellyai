<div>
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-semibold text-text-primary">Atendimentos</h1>
        <x-button wire:click="create">+ Novo atendimento</x-button>
    </div>

    <div class="flex flex-wrap gap-3 mb-4">
        <select wire:model.live="statusFilter" class="rounded-md bg-surface-card border-surface-border text-text-primary text-sm">
            <option value="">Status</option>
            @foreach (\App\Models\Atendimento\Atendimento::STATUSES as $value => $label)
                <option value="{{ $value }}">{{ $label }}</option>
            @endforeach
        </select>

        <select wire:model.live="canalFilter" class="rounded-md bg-surface-card border-surface-border text-text-primary text-sm">
            <option value="">Canal</option>
            @foreach ($canais as $canal)
                <option value="{{ $canal->id }}">{{ $canal->nome }}</option>
            @endforeach
        </select>
    </div>

    <x-table :headers="['#', 'Cliente', 'Canal', 'Setor', 'Status', 'Atendente', 'Aberto em', 'Ações']">
        @forelse ($atendimentos as $atendimento)
            <tr>
                <td class="px-4 py-3 text-text-secondary">#{{ $atendimento->id }}</td>
                <td class="px-4 py-3">{{ $atendimento->cliente->nome }}</td>
                <td class="px-4 py-3 text-text-secondary">{{ $atendimento->canal->nome }}</td>
                <td class="px-4 py-3 text-text-secondary">{{ \App\Models\Atendimento\Atendimento::SETORES[$atendimento->setor] ?? '—' }}</td>
                <td class="px-4 py-3">
                    <x-badge :variant="match ($atendimento->status) { 'resolvido' => 'success', 'aguardando' => 'warning', 'aberto' => 'primary', default => 'default' }">
                        {{ \App\Models\Atendimento\Atendimento::STATUSES[$atendimento->status] }}
                    </x-badge>
                </td>
                <td class="px-4 py-3 text-text-secondary">{{ $atendimento->assignedTo?->name ?? '—' }}</td>
                <td class="px-4 py-3 text-text-secondary">{{ $atendimento->created_at->format('d/m/Y H:i') }}</td>
                <td class="px-4 py-3 text-right whitespace-nowrap">
                    <a href="{{ route('atendimento.show', $atendimento) }}" class="text-primary hover:underline text-sm">Abrir</a>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="8" class="px-4 py-6 text-center text-text-secondary">Nenhum atendimento registrado.</td>
            </tr>
        @endforelse
    </x-table>

    <div class="mt-4">
        {{ $atendimentos->links() }}
    </div>

    <x-modal name="atendimento-form" title="Novo atendimento">
        <form wire:submit="save" class="space-y-4">
            <div>
                <label class="block text-sm font-medium mb-1 text-text-primary">Cliente</label>
                <select wire:model="clienteId" class="w-full rounded-md bg-surface border-surface-border text-text-primary">
                    <option value="">— Selecione —</option>
                    @foreach ($clientes as $cliente)
                        <option value="{{ $cliente->id }}">{{ $cliente->nome }}</option>
                    @endforeach
                </select>
                @error('clienteId') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
                @if ($clientes->isEmpty())
                    <p class="mt-1 text-xs text-text-secondary">Nenhum cliente cadastrado ainda. Crie um na tela de Clientes.</p>
                @endif
            </div>

            <div>
                <label class="block text-sm font-medium mb-1 text-text-primary">Canal</label>
                <select wire:model="canalId" class="w-full rounded-md bg-surface border-surface-border text-text-primary">
                    <option value="">— Selecione —</option>
                    @foreach ($canais as $canal)
                        <option value="{{ $canal->id }}">{{ $canal->nome }}</option>
                    @endforeach
                </select>
                @error('canalId') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
                @if ($canais->isEmpty())
                    <p class="mt-1 text-xs text-text-secondary">Nenhum canal ativo. Configure um na tela de Canais.</p>
                @endif
            </div>

            <div class="flex justify-end gap-3 pt-2">
                <x-button variant="secondary" type="button" @click="open = false">Cancelar</x-button>
                <x-button type="submit">Criar e abrir</x-button>
            </div>
        </form>
    </x-modal>
</div>
