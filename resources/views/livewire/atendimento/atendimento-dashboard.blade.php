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
            <p class="mt-2 text-2xl font-semibold text-accent">{{ $emAtendimento }}</p>
        </x-card>
        <x-card>
            <p class="text-xs text-text-secondary uppercase tracking-wide">Resolvidos</p>
            <p class="mt-2 text-2xl font-semibold text-success">{{ $resolvidos }}</p>
        </x-card>
        <x-card>
            <p class="text-xs text-text-secondary uppercase tracking-wide">Satisfação média</p>
            <p class="mt-2 text-2xl font-semibold text-warning">{{ $satisfacaoMedia ?: '—' }}</p>
        </x-card>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Coluna principal: preview da conversa + atividade --}}
        <div class="lg:col-span-2 space-y-6">
            <x-card title="Conversa mais recente" :badge="$ultimoAtendimento ? $ultimoAtendimento->canal->nome : null">
                @if ($ultimoAtendimento)
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <p class="text-sm font-medium text-text-primary">{{ $ultimoAtendimento->cliente->nome }}</p>
                            <p class="text-xs text-text-secondary">Atendimento #{{ $ultimoAtendimento->id }} · {{ $ultimoAtendimento->created_at->diffForHumans() }}</p>
                        </div>
                        <x-badge :variant="match ($ultimoAtendimento->status) { 'resolvido' => 'success', 'aguardando' => 'warning', 'aberto' => 'primary', default => 'default' }">
                            {{ \App\Models\Atendimento\Atendimento::STATUSES[$ultimoAtendimento->status] }}
                        </x-badge>
                    </div>

                    <div class="space-y-3 mb-4">
                        @forelse ($ultimasMensagens as $mensagem)
                            @if ($mensagem->remetente === 'atendente')
                                <div class="flex justify-end">
                                    <div class="bg-linear-to-r from-primary to-accent rounded-2xl rounded-tr-sm px-3 py-2 max-w-[80%]">
                                        <p class="text-xs text-white">{{ Str::limit($mensagem->conteudo, 100) }}</p>
                                    </div>
                                </div>
                            @elseif ($mensagem->remetente === 'ia')
                                <div class="flex items-start gap-2">
                                    <div class="w-6 h-6 rounded-full bg-accent/20 flex items-center justify-center shrink-0">
                                        <x-heroicon-s-cpu-chip class="w-3.5 h-3.5 text-accent" />
                                    </div>
                                    <div class="bg-accent/10 border border-accent/20 rounded-2xl rounded-tl-sm px-3 py-2 max-w-[80%]">
                                        <p class="text-xs text-text-primary">{{ Str::limit($mensagem->conteudo, 100) }}</p>
                                    </div>
                                </div>
                            @else
                                <div class="flex items-start gap-2">
                                    <div class="w-6 h-6 rounded-full bg-surface-border flex items-center justify-center text-[10px] font-semibold text-text-secondary shrink-0">
                                        {{ strtoupper(substr($ultimoAtendimento->cliente->nome, 0, 1)) }}
                                    </div>
                                    <div class="bg-surface border border-surface-border rounded-2xl rounded-tl-sm px-3 py-2 max-w-[80%]">
                                        <p class="text-xs text-text-primary">{{ Str::limit($mensagem->conteudo, 100) }}</p>
                                    </div>
                                </div>
                            @endif
                        @empty
                            <p class="text-sm text-text-secondary">Nenhuma mensagem registrada ainda.</p>
                        @endforelse
                    </div>

                    <a href="{{ route('atendimento.show', $ultimoAtendimento) }}" class="text-sm text-primary hover:underline">
                        Abrir conversa completa →
                    </a>
                @else
                    <p class="text-sm text-text-secondary">Nenhum atendimento registrado ainda.</p>
                @endif
            </x-card>

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

        {{-- Coluna lateral: analytics --}}
        <div class="space-y-6">
            <x-card title="Atendimentos (14 dias)">
                @php
                    $chartWidth = 260;
                    $chartHeight = 90;
                    $maxTrend = max(1, $trend->max('count'));
                    $points = $trend->values()->map(function ($point, $i) use ($trend, $maxTrend, $chartWidth, $chartHeight) {
                        $x = $trend->count() > 1 ? ($i / ($trend->count() - 1)) * $chartWidth : 0;
                        $y = $chartHeight - 10 - (($point['count'] / $maxTrend) * ($chartHeight - 20));

                        return ['x' => round($x, 1), 'y' => round($y, 1), 'label' => $point['label'], 'count' => $point['count']];
                    });
                    $linePath = $points->map(fn ($p, $i) => ($i === 0 ? 'M' : 'L').$p['x'].','.$p['y'])->implode(' ');
                    $areaPath = $linePath.' L'.$points->last()['x'].','.$chartHeight.' L'.$points->first()['x'].','.$chartHeight.' Z';
                    $last = $points->last();
                @endphp

                <svg viewBox="0 0 {{ $chartWidth }} {{ $chartHeight }}" class="w-full h-24" preserveAspectRatio="none" role="img" aria-label="Atendimentos criados por dia, últimos 14 dias">
                    <line x1="0" y1="{{ $chartHeight - 1 }}" x2="{{ $chartWidth }}" y2="{{ $chartHeight - 1 }}" stroke="var(--color-surface-border)" stroke-width="1" />
                    <path d="{{ $areaPath }}" fill="var(--color-primary)" fill-opacity="0.1" stroke="none" />
                    <path d="{{ $linePath }}" fill="none" stroke="var(--color-primary)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                    @foreach ($points as $i => $p)
                        <circle cx="{{ $p['x'] }}" cy="{{ $p['y'] }}" r="{{ $i === $points->count() - 1 ? 4 : 8 }}" fill="{{ $i === $points->count() - 1 ? 'var(--color-primary)' : 'transparent' }}" stroke="{{ $i === $points->count() - 1 ? 'var(--color-surface-card)' : 'none' }}" stroke-width="2">
                            <title>{{ $p['label'] }}: {{ $p['count'] }} atendimento(s)</title>
                        </circle>
                    @endforeach
                </svg>
                <div class="flex justify-between text-[10px] text-text-secondary mt-1">
                    <span>{{ $points->first()['label'] }}</span>
                    <span class="text-text-primary font-medium">{{ $last['label'] }}: {{ $last['count'] }}</span>
                </div>
            </x-card>

            <x-card title="Por status">
                @php
                    $statusMeta = [
                        'aberto' => ['label' => 'Aberto', 'var' => '--color-primary'],
                        'em_atendimento' => ['label' => 'Em atendimento', 'var' => '--color-accent'],
                        'aguardando' => ['label' => 'Aguardando', 'var' => '--color-warning'],
                        'resolvido' => ['label' => 'Resolvido', 'var' => '--color-success'],
                    ];
                    $totalStatus = max(1, $statusCounts->sum());
                @endphp

                <div class="flex h-6 w-full rounded-lg overflow-hidden gap-0.5 bg-surface">
                    @foreach ($statusMeta as $key => $meta)
                        @php $count = $statusCounts[$key] ?? 0; @endphp
                        @if ($count > 0)
                            <div style="width: {{ ($count / $totalStatus) * 100 }}%; background-color: var({{ $meta['var'] }})" title="{{ $meta['label'] }}: {{ $count }}"></div>
                        @endif
                    @endforeach
                </div>

                <div class="grid grid-cols-2 gap-x-3 gap-y-2 mt-4 text-xs">
                    @foreach ($statusMeta as $key => $meta)
                        <div class="flex items-center gap-1.5 text-text-secondary">
                            <span class="w-2 h-2 rounded-full shrink-0" style="background-color: var({{ $meta['var'] }})"></span>
                            {{ $meta['label'] }}
                            <span class="text-text-primary font-medium ml-auto">{{ $statusCounts[$key] ?? 0 }}</span>
                        </div>
                    @endforeach
                </div>
            </x-card>

            <x-card title="Por canal">
                @php
                    $topCanais = $canalCounts->take(5);
                    $outrosCanais = $canalCounts->count() > 5 ? $canalCounts->slice(5)->sum() : 0;
                    $maxCanal = max(1, $canalCounts->max() ?? 1);
                @endphp

                <div class="space-y-3">
                    @forelse ($topCanais as $canal => $count)
                        <div>
                            <div class="flex justify-between text-xs mb-1">
                                <span class="text-text-secondary">{{ $canal }}</span>
                                <span class="text-text-primary font-medium">{{ $count }}</span>
                            </div>
                            <div class="h-2 rounded-full bg-surface overflow-hidden">
                                <div class="h-full rounded-full bg-accent" style="width: {{ ($count / $maxCanal) * 100 }}%"></div>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-text-secondary">Sem dados ainda.</p>
                    @endforelse

                    @if ($outrosCanais > 0)
                        <p class="text-xs text-text-secondary pt-1">+ {{ $outrosCanais }} em outros canais</p>
                    @endif
                </div>
            </x-card>
        </div>
    </div>
</div>
