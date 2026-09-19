@php $primeiroNome = \Illuminate\Support\Str::before(auth()->user()->name, ' '); @endphp

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start mb-8">
    <div class="lg:col-span-2">
        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-primary/10 border border-primary/20 text-xs font-medium text-primary mb-4">
            <x-heroicon-o-sparkles class="w-3.5 h-3.5" /> Área do Aluno
        </span>
        <h1 class="text-4xl sm:text-5xl font-bold text-text-primary">Olá, {{ $primeiroNome }}!</h1>
        <p class="text-lg text-text-secondary mt-3">Continue sua jornada de aprendizado de onde parou hoje.</p>

        <div class="flex flex-wrap gap-3 mt-6">
            @if ($continuar['status'] === 'proximo')
                <a href="{{ $continuar['url'] }}" class="inline-flex items-center gap-2 px-6 py-3 rounded-xl font-semibold text-white bg-primary hover:brightness-110 shadow-lg shadow-primary/30 transition">
                    <x-heroicon-s-play class="w-4 h-4" /> Continuar estudando
                </a>
            @endif

            <a href="{{ $turmaRecente ? route('academico.minha-turma.turma', $turmaRecente) : route('academico.minha-turma.index') }}" class="inline-flex items-center gap-2 px-6 py-3 rounded-xl font-semibold text-text-primary bg-surface-card border border-surface-border hover:bg-surface-border/50 transition">
                <x-heroicon-o-book-open class="w-4 h-4" /> Ver módulos
            </a>
        </div>

        @if ($continuar['status'] === 'tudo-concluido')
            <p class="text-sm text-success mt-4">Você concluiu tudo disponível até agora! 🎉</p>
        @endif
    </div>

    @if ($turmaRecente)
        <x-card>
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-semibold uppercase tracking-wider text-text-secondary">Seu progresso</span>
                <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-primary/10 text-primary">{{ $resumo['percentual'] }}%</span>
            </div>
            <p class="text-4xl font-bold text-text-primary">{{ $resumo['concluidas'] }}<span class="text-base font-normal text-text-secondary"> de {{ $resumo['total'] }} aulas</span></p>
            <div class="w-full h-2 rounded-full bg-surface-border overflow-hidden mt-4">
                <div class="h-full rounded-full bg-primary" style="width: {{ $resumo['percentual'] }}%"></div>
            </div>
            <p class="text-xs text-text-secondary mt-2">{{ $resumo['restantes'] }} {{ $resumo['restantes'] === 1 ? 'aula restante' : 'aulas restantes' }}</p>
            <p class="text-xs text-text-secondary mt-1">{{ $turmaRecente->nome }} · {{ $turmaRecente->curso->nome }}</p>
        </x-card>
    @endif
</div>

@if (! $turmaRecente)
    <x-card class="mb-8">
        <p class="text-sm text-text-secondary">Você ainda não está matriculado em nenhuma turma.</p>
    </x-card>
@else
    @if ($bonus->isNotEmpty())
        <a href="{{ route('academico.minha-turma.turma', $turmaRecente) }}" class="group flex items-center gap-4 rounded-2xl border border-amber-400/30 bg-linear-to-r from-amber-400/15 via-emerald-400/10 to-sky-400/10 p-5 mb-8 hover:border-amber-400/60 transition">
            <span class="w-14 h-14 rounded-xl bg-linear-to-br from-amber-400 to-emerald-500 flex items-center justify-center shrink-0">
                <x-heroicon-o-gift class="w-7 h-7 text-white" />
            </span>
            <span class="flex-1 min-w-0">
                <span class="block text-[11px] font-bold uppercase tracking-wider text-amber-500">Conteúdos exclusivos</span>
                <span class="block text-lg font-semibold text-text-primary">Seus bônus do curso</span>
                <span class="block text-sm text-text-secondary truncate">{{ $bonus->take(3)->implode(' · ') }}</span>
            </span>
            <x-heroicon-o-chevron-right class="w-5 h-5 text-text-secondary group-hover:translate-x-0.5 transition" />
        </a>
    @endif

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
        <div class="flex items-center gap-4 rounded-2xl border border-emerald-500/20 bg-emerald-500/10 p-5">
            <span class="w-12 h-12 rounded-xl bg-emerald-500/15 flex items-center justify-center"><x-heroicon-o-check-circle class="w-6 h-6 text-emerald-500" /></span>
            <div>
                <p class="text-2xl font-bold text-text-primary">{{ $resumo['concluidas'] }}</p>
                <p class="text-xs text-text-secondary">Concluídas</p>
            </div>
        </div>
        <div class="flex items-center gap-4 rounded-2xl border border-amber-500/20 bg-amber-500/10 p-5">
            <span class="w-12 h-12 rounded-xl bg-amber-500/15 flex items-center justify-center"><x-heroicon-o-arrow-trending-up class="w-6 h-6 text-amber-500" /></span>
            <div>
                <p class="text-2xl font-bold text-text-primary">{{ $resumo['percentual'] }}%</p>
                <p class="text-xs text-text-secondary">Progresso</p>
            </div>
        </div>
    </div>

    <x-card class="mb-10">
        <div class="flex items-center justify-between mb-3">
            <span class="text-sm font-semibold text-text-primary">Progresso geral do curso</span>
            <span class="text-sm font-semibold text-primary">{{ $resumo['percentual'] }}%</span>
        </div>
        <div class="w-full h-2 rounded-full bg-surface-border overflow-hidden">
            <div class="h-full rounded-full bg-primary" style="width: {{ $resumo['percentual'] }}%"></div>
        </div>
        <p class="text-xs text-text-secondary mt-2">{{ $resumo['concluidas'] }} / {{ $resumo['total'] }} aulas concluídas</p>
    </x-card>

    @if ($continuar['status'] === 'proximo')
        <x-carrossel titulo="Continuar assistindo">
            <x-modulo-card
                :modulo="$continuar['conteudo']->modulo"
                numero="01"
                :href="$continuar['url']"
                :total="$continuar['conteudo']->modulo->conteudos()->count()"
            />
        </x-carrossel>
    @endif

    @foreach ($secoes as $secao)
        <x-carrossel :titulo="$secao['titulo']">
            @foreach ($secao['cards'] as $card)
                <x-modulo-card
                    :modulo="$card['modulo']"
                    :numero="$card['numero']"
                    :href="route('academico.minha-turma.modulo', [$turmaRecente, $card['modulo']])"
                    :total="$card['total']"
                    :concluidas="$card['concluidas']"
                    :bloqueio="$card['bloqueio']"
                />
            @endforeach
        </x-carrossel>
    @endforeach

    @if ($turmasDoAluno->count() > 1)
        <h2 class="text-xl font-semibold text-text-primary mb-4">Suas turmas</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-10">
            @foreach ($turmasDoAluno as $turma)
                <a href="{{ route('academico.minha-turma.turma', $turma) }}" class="block">
                    <x-card>
                        <div class="flex items-center justify-between mb-2">
                            <h3 class="text-base font-semibold text-text-primary">{{ $turma->nome }}</h3>
                            <x-badge variant="primary">{{ $turma->curso->nome }}</x-badge>
                        </div>
                        <p class="text-xs text-text-secondary">{{ $turma->percentualConcluido(auth()->user()) }}% concluído</p>
                    </x-card>
                </a>
            @endforeach
        </div>
    @endif
@endif
