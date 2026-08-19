<div class="max-w-5xl">
    <a href="{{ route('atendimento.index') }}" class="inline-flex items-center gap-2 text-sm text-text-secondary hover:text-text-primary mb-4">
        <x-heroicon-o-arrow-left class="w-4 h-4" />
        Voltar para atendimentos
    </a>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2">
            <div class="glow-border bg-surface-card/80 backdrop-blur border border-surface-border rounded-2xl flex flex-col h-[600px] overflow-hidden">
                <div class="flex items-center justify-between px-6 py-4 border-b border-surface-border">
                    <div>
                        <p class="text-text-primary font-semibold">{{ $atendimento->cliente->nome }}</p>
                        <p class="text-xs text-text-secondary">{{ $atendimento->canal->nome }} · Atendimento #{{ $atendimento->id }}</p>
                    </div>
                    <x-badge :variant="match ($atendimento->status) { 'resolvido' => 'success', 'aguardando' => 'warning', 'aberto' => 'primary', default => 'default' }">
                        {{ \App\Models\Atendimento\Atendimento::STATUSES[$atendimento->status] }}
                    </x-badge>
                </div>

                <div class="flex-1 overflow-y-auto px-6 py-4 space-y-4">
                    @forelse ($mensagens as $mensagem)
                        @if ($mensagem->remetente === 'cliente')
                            <div class="flex items-start gap-2">
                                <div class="w-7 h-7 rounded-full bg-surface-border flex items-center justify-center text-xs font-semibold text-text-secondary shrink-0">
                                    {{ strtoupper(substr($atendimento->cliente->nome, 0, 1)) }}
                                </div>
                                <div class="bg-surface border border-surface-border rounded-2xl rounded-tl-sm px-4 py-2 max-w-md">
                                    <p class="text-sm text-text-primary">{{ $mensagem->conteudo }}</p>
                                    <p class="text-[10px] text-text-secondary mt-1">{{ $mensagem->created_at->format('H:i') }}</p>
                                </div>
                            </div>
                        @elseif ($mensagem->remetente === 'ia')
                            <div class="flex items-start gap-2">
                                <div class="w-7 h-7 rounded-full bg-accent/20 flex items-center justify-center shrink-0">
                                    <x-heroicon-s-cpu-chip class="w-4 h-4 text-accent" />
                                </div>
                                <div class="bg-accent/10 border border-accent/20 rounded-2xl rounded-tl-sm px-4 py-2 max-w-md">
                                    <p class="text-sm text-text-primary">{{ $mensagem->conteudo }}</p>
                                    <p class="text-[10px] text-text-secondary mt-1">IA · {{ $mensagem->created_at->format('H:i') }}</p>
                                </div>
                            </div>
                        @else
                            <div class="flex items-start gap-2 justify-end">
                                <div class="bg-linear-to-r from-primary to-accent rounded-2xl rounded-tr-sm px-4 py-2 max-w-md">
                                    <p class="text-sm text-white">{{ $mensagem->conteudo }}</p>
                                    <p class="text-[10px] text-white/70 mt-1">{{ $mensagem->autor?->name ?? 'Atendente' }} · {{ $mensagem->created_at->format('H:i') }}</p>
                                </div>
                                <div class="w-7 h-7 rounded-full bg-linear-to-br from-primary to-accent flex items-center justify-center text-white text-xs font-semibold shrink-0">
                                    {{ strtoupper(substr($mensagem->autor?->name ?? 'A', 0, 1)) }}
                                </div>
                            </div>
                        @endif
                    @empty
                        <p class="text-sm text-text-secondary text-center mt-10">Nenhuma mensagem ainda.</p>
                    @endforelse
                </div>

                <form wire:submit="enviarMensagem" class="border-t border-surface-border p-4 space-y-2">
                    <div class="flex gap-2">
                        <select wire:model="remetente" class="rounded-md bg-surface border-surface-border text-text-primary text-xs">
                            <option value="cliente">Simular cliente</option>
                            <option value="atendente">Responder como atendente</option>
                        </select>
                    </div>
                    <div class="flex gap-2">
                        <input
                            type="text"
                            wire:model="mensagem"
                            placeholder="Digite uma mensagem..."
                            class="flex-1 rounded-md bg-surface border-surface-border text-text-primary text-sm"
                        >
                        <x-button type="submit">Enviar</x-button>
                    </div>
                    @error('mensagem') <p class="text-sm text-danger">{{ $message }}</p> @enderror
                </form>
            </div>
        </div>

        <div class="space-y-4">
            <x-card title="Detalhes">
                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-medium text-text-secondary mb-1">Status</label>
                        <select wire:change="atualizarStatus($event.target.value)" class="w-full rounded-md bg-surface border-surface-border text-text-primary text-sm">
                            @foreach (\App\Models\Atendimento\Atendimento::STATUSES as $value => $label)
                                <option value="{{ $value }}" @selected($atendimento->status === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-text-secondary mb-1">Setor</label>
                        <select wire:change="atualizarSetor($event.target.value)" class="w-full rounded-md bg-surface border-surface-border text-text-primary text-sm">
                            <option value="">— Nenhum —</option>
                            @foreach (\App\Models\Atendimento\Atendimento::SETORES as $value => $label)
                                <option value="{{ $value }}" @selected($atendimento->setor === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-text-secondary mb-1">Atendente</label>
                        @if ($atendimento->assignedTo)
                            <p class="text-sm text-text-primary">{{ $atendimento->assignedTo->name }}</p>
                        @else
                            <x-button type="button" variant="secondary" wire:click="atribuirParaMim" class="w-full text-xs">
                                Atribuir para mim
                            </x-button>
                        @endif
                    </div>
                </div>
            </x-card>

            <x-card title="Cliente">
                <p class="text-sm text-text-primary">{{ $atendimento->cliente->nome }}</p>
                <p class="text-xs text-text-secondary mt-1">{{ $atendimento->cliente->email ?? '—' }}</p>
                <p class="text-xs text-text-secondary">{{ $atendimento->cliente->telefone ?? '—' }}</p>
            </x-card>
        </div>
    </div>
</div>
