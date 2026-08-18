<div>
    <h1 class="text-2xl font-semibold text-text-primary mb-6">Auditorias</h1>

    <x-table :headers="['ID', 'Usuário', 'Evento', 'Tags', 'Data', 'Ações']">
        @foreach ($audits as $audit)
            <tr>
                <td class="px-4 py-3">{{ $audit->id }}</td>
                <td class="px-4 py-3">{{ $audit->user?->name ?? '—' }}</td>
                <td class="px-4 py-3">
                    <x-badge :variant="match ($audit->event) { 'created' => 'success', 'deleted' => 'danger', default => 'warning' }">
                        {{ $audit->event }}
                    </x-badge>
                </td>
                <td class="px-4 py-3 text-text-secondary">{{ $audit->tags ?? '—' }}</td>
                <td class="px-4 py-3 text-text-secondary">{{ $audit->created_at->format('d/m/Y, H:i:s') }}</td>
                <td class="px-4 py-3 text-right whitespace-nowrap space-x-3">
                    <button wire:click="view({{ $audit->id }})" class="text-primary hover:underline text-sm">Visualizar</button>
                    <button
                        wire:click="delete({{ $audit->id }})"
                        wire:confirm="Remover este registro de auditoria?"
                        class="text-danger hover:underline text-sm"
                    >
                        Excluir
                    </button>
                </td>
            </tr>
        @endforeach
    </x-table>

    <div class="mt-4">
        {{ $audits->links() }}
    </div>

    <x-modal name="audit-view" title="Detalhes da auditoria">
        @if ($this->viewing)
            <div class="space-y-3 text-sm">
                <div>
                    <span class="text-text-secondary">Model:</span>
                    <span class="text-text-primary font-mono">{{ $this->viewing->auditable_type }} #{{ $this->viewing->auditable_id }}</span>
                </div>
                <div>
                    <span class="text-text-secondary">Usuário:</span>
                    <span class="text-text-primary">{{ $this->viewing->user?->name ?? '—' }}</span>
                </div>
                <div>
                    <span class="text-text-secondary">URL:</span>
                    <span class="text-text-primary break-all">{{ $this->viewing->url ?? '—' }}</span>
                </div>
                <div>
                    <span class="text-text-secondary">IP:</span>
                    <span class="text-text-primary">{{ $this->viewing->ip_address ?? '—' }}</span>
                </div>
                <div>
                    <p class="text-text-secondary mb-1">Valores anteriores:</p>
                    <pre class="bg-surface rounded-md p-3 text-xs overflow-x-auto text-text-primary">{{ json_encode($this->viewing->old_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                </div>
                <div>
                    <p class="text-text-secondary mb-1">Valores novos:</p>
                    <pre class="bg-surface rounded-md p-3 text-xs overflow-x-auto text-text-primary">{{ json_encode($this->viewing->new_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                </div>
            </div>
        @endif
    </x-modal>
</div>
