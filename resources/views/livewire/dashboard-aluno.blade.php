<div class="flex items-center justify-between mb-6 flex-wrap gap-3">
    <div>
        <h1 class="text-2xl font-semibold text-text-primary">Olá, {{ auth()->user()->name }}!</h1>
        <p class="text-sm text-text-secondary">Vamos continuar de onde você parou.</p>
    </div>

    <div class="flex gap-3">
        @if ($continuar['status'] === 'proximo')
            <a href="{{ $continuar['url'] }}" class="inline-flex items-center justify-center px-4 py-2 rounded-full font-medium text-sm bg-linear-to-r from-primary to-accent text-white shadow-lg shadow-primary/25 hover:brightness-110">
                Continuar estudando
            </a>
        @endif

        <a href="{{ route('academico.minha-turma.index') }}" class="inline-flex items-center justify-center px-4 py-2 rounded-full font-medium text-sm bg-surface-card text-text-primary border border-surface-border hover:bg-surface-border">
            Ver módulos
        </a>
    </div>
</div>

@if ($continuar['status'] === 'tudo-concluido')
    <x-card class="mb-6">
        <p class="text-sm text-success">Você concluiu tudo disponível até agora! 🎉</p>
    </x-card>
@endif

@if ($turmaRecente)
    @php
        $aluno = auth()->user();
        $total = $turmaRecente->totalAulas();
        $concluidas = $turmaRecente->aulasConcluidasPor($aluno);
        $percentual = $turmaRecente->percentualConcluido($aluno);
    @endphp

    <x-card title="Seu progresso" class="mb-6">
        <p class="text-sm text-text-secondary mb-2">{{ $turmaRecente->nome }} — {{ $concluidas }} / {{ $total }} aulas</p>
        <div class="w-full h-2 rounded-full bg-surface-border overflow-hidden">
            <div class="h-full rounded-full bg-linear-to-r from-primary to-accent" style="width: {{ $percentual }}%"></div>
        </div>
        <p class="text-xs text-text-secondary mt-1">{{ $percentual }}% concluído</p>
    </x-card>
@endif

<div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
    @forelse ($turmasDoAluno as $turma)
        <a href="{{ route('academico.minha-turma.turma', $turma) }}" class="block">
            <x-card>
                <div class="flex items-center justify-between mb-2">
                    <h2 class="text-base font-semibold text-text-primary">{{ $turma->nome }}</h2>
                    <x-badge variant="primary">{{ $turma->curso->nome }}</x-badge>
                </div>
                <p class="text-xs text-text-secondary">{{ $turma->percentualConcluido(auth()->user()) }}% concluído</p>
            </x-card>
        </a>
    @empty
        <p class="text-sm text-text-secondary">Você ainda não está matriculado em nenhuma turma.</p>
    @endforelse
</div>

<x-card title="Notificações recentes" :badge="$recentNotifications->isNotEmpty() ? $recentNotifications->count().' eventos' : null">
    @forelse ($recentNotifications as $notification)
        <div class="flex items-start gap-3 py-3 {{ !$loop->last ? 'border-b border-surface-border' : '' }}">
            <div class="w-8 h-8 rounded-full bg-primary/15 flex items-center justify-center shrink-0 mt-0.5">
                <x-heroicon-o-bell class="w-4 h-4 text-accent" />
            </div>
            <div class="min-w-0">
                <p class="text-sm text-text-primary font-medium">{{ $notification->data['title'] ?? 'Notificação' }}</p>
                <p class="text-xs text-text-secondary mt-0.5">{{ $notification->data['message'] ?? '' }}</p>
                <p class="text-xs text-text-secondary/70 mt-1">{{ $notification->created_at->diffForHumans() }}</p>
            </div>
        </div>
    @empty
        <p class="text-sm text-text-secondary">Nenhuma notificação registrada.</p>
    @endforelse
</x-card>
