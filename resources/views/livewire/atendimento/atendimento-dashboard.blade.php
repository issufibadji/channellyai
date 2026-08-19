<div>
    <div class="flex items-center gap-3 mb-6">
        <div class="w-10 h-10 rounded-xl bg-linear-to-br from-primary to-accent flex items-center justify-center shadow-lg shadow-primary/30">
            <x-heroicon-s-chat-bubble-left-right class="w-5 h-5 text-white" />
        </div>
        <div>
            <h1 class="text-2xl font-semibold text-text-primary">Atendimento com IA</h1>
            <p class="text-sm text-text-secondary">Visão geral dos atendimentos multicanal.</p>
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
        <x-card>
            <p class="text-xs text-text-secondary uppercase tracking-wide">Total</p>
            <p class="mt-2 text-2xl font-semibold text-text-primary">{{ $total }}</p>
        </x-card>
        <x-card>
            <p class="text-xs text-text-secondary uppercase tracking-wide">Abertos</p>
            <p class="mt-2 text-2xl font-semibold text-primary">{{ $abertos }}</p>
        </x-card>
        <x-card>
            <p class="text-xs text-text-secondary uppercase tracking-wide">Em atendimento</p>
            <p class="mt-2 text-2xl font-semibold text-warning">{{ $emAtendimento }}</p>
        </x-card>
        <x-card>
            <p class="text-xs text-text-secondary uppercase tracking-wide">Resolvidos</p>
            <p class="mt-2 text-2xl font-semibold text-success">{{ $resolvidos }}</p>
        </x-card>
        <x-card>
            <p class="text-xs text-text-secondary uppercase tracking-wide">Satisfação média</p>
            <p class="mt-2 text-2xl font-semibold text-accent">{{ $satisfacaoMedia ?: '—' }}</p>
        </x-card>
    </div>

    <x-card title="Atendimentos recentes">
        @forelse ($recentes as $atendimento)
            <div class="flex items-center justify-between py-3 {{ !$loop->last ? 'border-b border-surface-border' : '' }}">
                <div>
                    <a href="{{ route('atendimento.show', $atendimento) }}" class="text-sm font-medium text-text-primary hover:text-primary">
                        {{ $atendimento->cliente->nome }}
                    </a>
                    <p class="text-xs text-text-secondary">{{ $atendimento->canal->nome }} · {{ $atendimento->created_at->diffForHumans() }}</p>
                </div>
                <x-badge :variant="match ($atendimento->status) { 'resolvido' => 'success', 'aguardando' => 'warning', 'aberto' => 'primary', default => 'default' }">
                    {{ \App\Models\Atendimento\Atendimento::STATUSES[$atendimento->status] }}
                </x-badge>
            </div>
        @empty
            <p class="text-sm text-text-secondary">Nenhum atendimento registrado ainda.</p>
        @endforelse
    </x-card>
</div>
