@php $primeiroNome = \Illuminate\Support\Str::before(auth()->user()->name, ' '); @endphp

<div class="flex items-start justify-between flex-wrap gap-4 mb-8">
    <div>
        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-primary/10 border border-primary/20 text-xs font-medium text-primary mb-4">
            <x-heroicon-o-academic-cap class="w-3.5 h-3.5" /> Área do Professor
        </span>
        <h1 class="text-4xl sm:text-5xl font-bold text-text-primary">Olá, {{ $primeiroNome }}!</h1>
        <p class="text-lg text-text-secondary mt-3">Acompanhe suas turmas e quem precisa de atenção hoje.</p>
    </div>

    <div class="flex flex-wrap gap-3">
        <a href="{{ route('academico.minhas-turmas.index') }}" class="inline-flex items-center gap-2 px-5 py-3 rounded-xl font-semibold text-white bg-primary hover:brightness-110 shadow-lg shadow-primary/30 transition">
            <x-heroicon-o-rectangle-group class="w-4 h-4" /> Minhas turmas
        </a>
        <a href="{{ route('academico.meus-alunos.index') }}" class="inline-flex items-center gap-2 px-5 py-3 rounded-xl font-semibold text-text-primary bg-surface-card border border-surface-border hover:bg-surface-border/50 transition">
            <x-heroicon-o-user-group class="w-4 h-4" /> Meus alunos
        </a>
    </div>
</div>

<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
    <div class="rounded-2xl border border-sky-500/20 bg-sky-500/10 p-5">
        <x-heroicon-o-rectangle-group class="w-6 h-6 text-sky-500 mb-3" />
        <p class="text-3xl font-bold text-text-primary">{{ $resumoProfessor['turmas'] }}</p>
        <p class="text-xs text-text-secondary">{{ $resumoProfessor['turmas'] === 1 ? 'Turma' : 'Turmas' }}</p>
    </div>
    <div class="rounded-2xl border border-violet-500/20 bg-violet-500/10 p-5">
        <x-heroicon-o-user-group class="w-6 h-6 text-violet-500 mb-3" />
        <p class="text-3xl font-bold text-text-primary">{{ $resumoProfessor['alunos'] }}</p>
        <p class="text-xs text-text-secondary">{{ $resumoProfessor['alunos'] === 1 ? 'Aluno' : 'Alunos' }}</p>
    </div>
    <div class="rounded-2xl border border-emerald-500/20 bg-emerald-500/10 p-5">
        <x-heroicon-o-play-circle class="w-6 h-6 text-emerald-500 mb-3" />
        <p class="text-3xl font-bold text-text-primary">{{ $resumoProfessor['aulas'] }}</p>
        <p class="text-xs text-text-secondary">Aulas publicadas</p>
    </div>
    <div class="rounded-2xl border border-amber-500/20 bg-amber-500/10 p-5">
        <x-heroicon-o-arrow-trending-up class="w-6 h-6 text-amber-500 mb-3" />
        <p class="text-3xl font-bold text-text-primary">{{ $resumoProfessor['progressoMedio'] }}%</p>
        <p class="text-xs text-text-secondary">Progresso médio dos alunos</p>
    </div>
</div>

<h2 class="text-xl font-semibold text-text-primary mb-4">Suas turmas</h2>

@if ($turmasDoProfessor->isEmpty())
    <x-card class="mb-8">
        <p class="text-sm text-text-secondary">Você ainda não é responsável por nenhuma turma. Quando um gestor atribuir uma turma a você, ela aparece aqui.</p>
    </x-card>
@else
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-10">
        @foreach ($turmasDoProfessor as $turma)
            <x-card>
                <div class="flex items-start justify-between gap-3 mb-4">
                    <div class="min-w-0">
                        <h3 class="text-lg font-semibold text-text-primary truncate">{{ $turma->nome }}</h3>
                        <p class="text-sm text-text-secondary">{{ $turma->curso->nome }}</p>
                    </div>
                    <x-badge :variant="$turma->ativo ? 'success' : 'default'">{{ $turma->ativo ? 'Ativa' : 'Inativa' }}</x-badge>
                </div>

                <div class="grid grid-cols-3 gap-3 text-center mb-4">
                    <div>
                        <p class="text-xl font-bold text-text-primary">{{ $turma->progressoAlunos->count() }}</p>
                        <p class="text-[11px] text-text-secondary">alunos</p>
                    </div>
                    <div>
                        <p class="text-xl font-bold text-text-primary">{{ $turma->modulos_count }}</p>
                        <p class="text-[11px] text-text-secondary">módulos</p>
                    </div>
                    <div>
                        <p class="text-xl font-bold text-text-primary">{{ $turma->totalAulasNivel }}</p>
                        <p class="text-[11px] text-text-secondary">aulas</p>
                    </div>
                </div>

                <div class="flex items-center justify-between text-xs text-text-secondary mb-1">
                    <span>Progresso médio da turma</span>
                    <span class="font-semibold text-primary">{{ $turma->progressoMedio }}%</span>
                </div>
                <div class="w-full h-2 rounded-full bg-surface-border overflow-hidden mb-4">
                    <div class="h-full rounded-full bg-primary" style="width: {{ $turma->progressoMedio }}%"></div>
                </div>

                <div class="flex flex-wrap gap-4 text-sm">
                    <a href="{{ route('academico.turmas.conteudo', $turma) }}" class="text-primary hover:underline">Módulos e conteúdo</a>
                    <a href="{{ route('academico.turmas.ao-vivo', $turma) }}" class="text-primary hover:underline">Aulas ao vivo</a>
                    @can('manage-matriculas')
                        <a href="{{ route('academico.turmas.matricula', $turma) }}" class="text-primary hover:underline">Matrícula</a>
                    @endcan
                </div>
            </x-card>
        @endforeach
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <x-card title="Alunos que precisam de atenção" :badge="$alunosEmAtencao->isNotEmpty() ? $alunosEmAtencao->count().' alunos' : null">
            @forelse ($alunosEmAtencao as $linha)
                <div class="flex items-center gap-3 py-3 {{ ! $loop->last ? 'border-b border-surface-border' : '' }}">
                    <div class="w-9 h-9 rounded-full bg-amber-500/15 flex items-center justify-center text-sm font-semibold text-amber-500 shrink-0">
                        {{ strtoupper(mb_substr($linha['aluno']->name, 0, 1)) }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-medium text-text-primary truncate">{{ $linha['aluno']->name }}</p>
                        <p class="text-xs text-text-secondary truncate">
                            {{ $linha['turma']->nome }} ·
                            @if ($linha['ultima_atividade'])
                                última atividade {{ $linha['ultima_atividade']->locale('pt_BR')->diffForHumans() }}
                            @else
                                ainda não concluiu nenhuma aula
                            @endif
                        </p>
                    </div>
                    <span class="text-xs font-semibold text-text-secondary">{{ $linha['percentual'] }}%</span>
                </div>
            @empty
                <p class="text-sm text-text-secondary">Nenhum aluno parado — todo mundo teve atividade nos últimos 7 dias. 🎉</p>
            @endforelse
        </x-card>

        <x-card title="Atividade recente" :badge="$atividadeRecente->isNotEmpty() ? $atividadeRecente->count().' conclusões' : null">
            @forelse ($atividadeRecente as $registro)
                <div class="flex items-start gap-3 py-3 {{ ! $loop->last ? 'border-b border-surface-border' : '' }}">
                    <div class="w-8 h-8 rounded-full bg-emerald-500/15 flex items-center justify-center shrink-0 mt-0.5">
                        <x-heroicon-o-check class="w-4 h-4 text-emerald-500" />
                    </div>
                    <div class="min-w-0">
                        <p class="text-sm text-text-primary"><span class="font-medium">{{ $registro->aluno->name }}</span> concluiu <span class="font-medium">{{ $registro->conteudo->titulo }}</span></p>
                        <p class="text-xs text-text-secondary/70 mt-0.5">{{ $registro->conteudo->modulo->nome }} · {{ $registro->concluido_em->locale('pt_BR')->diffForHumans() }}</p>
                    </div>
                </div>
            @empty
                <p class="text-sm text-text-secondary">Nenhuma aula concluída pelos seus alunos ainda.</p>
            @endforelse
        </x-card>
    </div>
@endif
