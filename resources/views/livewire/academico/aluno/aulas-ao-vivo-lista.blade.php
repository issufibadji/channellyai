<div class="max-w-4xl mx-auto">
    <h1 class="text-2xl font-semibold text-text-primary mb-6">Aulas ao vivo</h1>

    <h2 class="text-lg font-semibold text-text-primary mb-3">Próximas</h2>
    <div class="space-y-3 mb-10">
        @forelse ($proximas as $aula)
            <a href="{{ route('academico.ao-vivo.sala', $aula) }}" class="block">
                <x-card class="hover:border-primary/50 transition">
                    <div class="flex items-center gap-4">
                        <span class="w-11 h-11 rounded-xl {{ $aula->estaAoVivo() ? 'bg-red-500/15' : 'bg-primary/15' }} flex items-center justify-center shrink-0">
                            <x-heroicon-o-video-camera class="w-5 h-5 {{ $aula->estaAoVivo() ? 'text-red-500' : 'text-accent' }}" />
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="text-text-primary font-medium">{{ $aula->titulo }}</p>
                            <p class="text-xs text-text-secondary">{{ $aula->turma->nome }} · {{ $aula->inicio_em->format('d/m/Y \à\s H:i') }} · {{ $aula->duracao_minutos }} min</p>
                        </div>
                        @if ($aula->estaAoVivo())
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-red-500/15 text-red-500 text-xs font-semibold">
                                <span class="w-1.5 h-1.5 rounded-full bg-red-500 animate-pulse"></span> AO VIVO — entrar
                            </span>
                        @else
                            <x-badge variant="primary">Agendada</x-badge>
                        @endif
                    </div>
                </x-card>
            </a>
        @empty
            <p class="text-sm text-text-secondary">Nenhuma aula ao vivo agendada pras suas turmas.</p>
        @endforelse
    </div>

    @if ($anteriores->isNotEmpty())
        <h2 class="text-lg font-semibold text-text-primary mb-3">Encerradas</h2>
        <div class="space-y-2">
            @foreach ($anteriores as $aula)
                <x-card>
                    <div class="flex items-center justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-sm text-text-primary font-medium">{{ $aula->titulo }}</p>
                            <p class="text-xs text-text-secondary">{{ $aula->turma->nome }} · {{ $aula->inicio_em->format('d/m/Y') }}</p>
                        </div>
                        @if ($aula->conteudo)
                            <a href="{{ route('academico.minha-turma.aula', [$aula->turma, $aula->conteudo]) }}" class="inline-flex items-center gap-1.5 text-sm text-primary hover:underline whitespace-nowrap">
                                <x-heroicon-s-play class="w-4 h-4" /> Assistir gravação
                            </a>
                        @else
                            <x-badge>Encerrada</x-badge>
                        @endif
                    </div>
                </x-card>
            @endforeach
        </div>
    @endif
</div>
