<div>
    <h1 class="text-2xl font-semibold text-text-primary mb-6">Relatórios de Atendimento</h1>

    <div class="flex flex-wrap items-end gap-3 mb-6">
        <div>
            <label class="block text-xs font-medium text-text-secondary mb-1">De</label>
            <input type="date" wire:model.live="dataInicio" class="rounded-md bg-surface-card border-surface-border text-text-primary text-sm">
        </div>
        <div>
            <label class="block text-xs font-medium text-text-secondary mb-1">Até</label>
            <input type="date" wire:model.live="dataFim" class="rounded-md bg-surface-card border-surface-border text-text-primary text-sm">
        </div>
        <x-card class="py-3 px-4">
            <p class="text-xs text-text-secondary uppercase tracking-wide">Total no período</p>
            <p class="text-xl font-semibold text-text-primary">{{ $total }}</p>
        </x-card>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <x-card title="Por status">
            @forelse (\App\Models\Atendimento\Atendimento::STATUSES as $value => $label)
                <div class="flex items-center justify-between py-2 text-sm">
                    <span class="text-text-secondary">{{ $label }}</span>
                    <span class="text-text-primary font-semibold">{{ $porStatus[$value] ?? 0 }}</span>
                </div>
            @empty
            @endforelse
        </x-card>

        <x-card title="Por canal">
            @forelse ($porCanal as $canal => $total)
                <div class="flex items-center justify-between py-2 text-sm">
                    <span class="text-text-secondary">{{ $canal }}</span>
                    <span class="text-text-primary font-semibold">{{ $total }}</span>
                </div>
            @empty
                <p class="text-sm text-text-secondary">Sem dados no período.</p>
            @endforelse
        </x-card>

        <x-card title="Por setor">
            @forelse ($porSetor as $setor => $total)
                <div class="flex items-center justify-between py-2 text-sm">
                    <span class="text-text-secondary">{{ \App\Models\Atendimento\Atendimento::SETORES[$setor] ?? $setor }}</span>
                    <span class="text-text-primary font-semibold">{{ $total }}</span>
                </div>
            @empty
                <p class="text-sm text-text-secondary">Sem dados no período.</p>
            @endforelse
        </x-card>
    </div>
</div>
