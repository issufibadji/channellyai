<div class="max-w-6xl mx-auto" @if (! $aula->estaAoVivo() && ! $ehProfessor && ! $aula->estaEncerrada()) wire:poll.8s @endif>
    <div class="flex items-start justify-between gap-4 flex-wrap mb-4">
        <div>
            <a href="{{ $ehProfessor ? route('academico.turmas.ao-vivo', $aula->turma) : route('academico.ao-vivo.index') }}" class="text-sm text-primary hover:underline">&larr; Aulas ao vivo</a>
            <div class="flex items-center gap-3 mt-1 flex-wrap">
                <h1 class="text-2xl font-semibold text-text-primary">{{ $aula->titulo }}</h1>
                @if ($aula->estaAoVivo())
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-red-500/15 text-red-500 text-xs font-semibold">
                        <span class="w-1.5 h-1.5 rounded-full bg-red-500 animate-pulse"></span> AO VIVO
                    </span>
                @endif
            </div>
            <p class="text-sm text-text-secondary">{{ $aula->turma->nome }} · {{ $aula->inicio_em->format('d/m/Y \à\s H:i') }} · {{ $aula->duracao_minutos }} min</p>
        </div>

        @if ($ehProfessor && ! $aula->estaEncerrada())
            <div class="flex gap-3">
                @if ($aula->estaAoVivo())
                    <x-button variant="secondary" wire:click="encerrar" wire:confirm="Encerrar a aula pra todos?">Encerrar aula</x-button>
                @endif
            </div>
        @endif
    </div>

    @if ($aula->descricao)
        <p class="text-sm text-text-secondary whitespace-pre-line mb-4">{{ $aula->descricao }}</p>
    @endif

    @if ($aula->estaEncerrada())
        <x-card>
            <div class="flex flex-col items-center text-center py-12 gap-2">
                <x-heroicon-o-check-circle class="w-10 h-10 text-text-secondary" />
                <p class="text-text-primary font-medium">Esta aula ao vivo já foi encerrada.</p>
            </div>
        </x-card>
    @elseif ($aula->estaAoVivo())
        <div class="rounded-2xl overflow-hidden border border-surface-border bg-black">
            <iframe
                src="{{ $urlSala }}"
                class="w-full h-[75vh]"
                allow="camera; microphone; fullscreen; display-capture; autoplay; clipboard-write"
                allowfullscreen
            ></iframe>
        </div>
    @else
        <x-card>
            <div class="flex flex-col items-center text-center py-12 gap-3">
                <x-heroicon-o-video-camera class="w-10 h-10 text-text-secondary" />
                @if ($ehProfessor)
                    <p class="text-text-primary font-medium">A aula está agendada e ainda não começou.</p>
                    <x-button wire:click="iniciar">Iniciar aula agora</x-button>
                @else
                    <p class="text-text-primary font-medium">Aguardando o professor iniciar a aula…</p>
                    <p class="text-sm text-text-secondary">Esta página atualiza sozinha assim que a sala abrir.</p>
                @endif
            </div>
        </x-card>
    @endif
</div>
