<div>
    <div class="flex items-center justify-between mb-2">
        <h1 class="text-2xl font-semibold text-text-primary">Agentes</h1>
    </div>
    <p class="text-sm text-text-secondary mb-6">
        Cada agente tem seu próprio persona, serviços e canais conectados — um estabelecimento pode ter mais de um (ex.: agendamento e suporte).
    </p>

    @if (session('success'))
        <x-alert variant="success" class="mb-6">{{ session('success') }}</x-alert>
    @endif

    <x-card class="max-w-2xl mb-6">
        <h2 class="font-semibold text-text-primary mb-3">Novo agente</h2>
        <form wire:submit="criar" class="flex items-center gap-3">
            <input type="text" wire:model="nomeNovoAgente" placeholder="Nome do agente (ex.: Agendamento)" class="w-full rounded-md bg-surface border-surface-border text-text-primary">
            <x-button type="submit" wire:loading.attr="disabled">Criar</x-button>
        </form>
        @error('nomeNovoAgente') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
    </x-card>

    <div class="max-w-2xl space-y-3">
        @forelse ($agentes as $agente)
            <x-card class="flex items-center justify-between">
                <div>
                    <div class="font-semibold text-text-primary">{{ $agente->dadosNegocio?->nome_exibicao ?: $agente->nome }}</div>
                    <div class="text-xs text-text-secondary flex items-center gap-2 mt-1">
                        <span class="font-mono">{{ $agente->group_folder }}</span>
                        <x-badge :variant="match ($agente->status_publicacao) {
                            'publicado' => 'success',
                            'erro' => 'danger',
                            default => 'default',
                        }">
                            {{ \App\Models\Agente::STATUS_PUBLICACAO[$agente->status_publicacao] ?? $agente->status_publicacao }}
                        </x-badge>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <a href="{{ route('atendimento.chatbot.show', $agente) }}" wire:navigate>
                        <x-button type="button" variant="secondary">Gerenciar</x-button>
                    </a>
                    <x-button
                        type="button"
                        variant="secondary"
                        wire:click="excluir({{ $agente->id }})"
                        wire:confirm="Excluir este agente? Os dados de negócio e serviços dele são apagados junto; os canais conectados ficam desvinculados, mas o histórico de atendimentos é mantido. Essa ação não pode ser desfeita."
                    >
                        Excluir
                    </x-button>
                </div>
            </x-card>
        @empty
            <p class="text-sm text-text-secondary">Nenhum agente ainda — crie o primeiro acima.</p>
        @endforelse
    </div>
</div>
