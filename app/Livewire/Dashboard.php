<?php

namespace App\Livewire;

use App\Models\AlunoProgresso;
use App\Models\AulaAoVivo;
use App\Models\Turma;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.master')]
class Dashboard extends Component
{
    public function render()
    {
        $user = Auth::user();
        $isAluno = $user->hasRole('aluno');
        $isProfessor = ! $isAluno && $user->hasRole('professor');

        $data = [
            'recentNotifications' => $user->notifications()->latest()->limit(5)->get(),
            'unreadCount' => $user->unreadNotifications()->count(),
            'roleName' => $user->getRoleNames()->first() ?? 'Sem função',
            'memberSince' => $user->created_at,
            'isAluno' => $isAluno,
            'isProfessor' => $isProfessor,
        ];

        if ($isAluno) {
            $data = array_merge($data, $this->dadosDoAluno($user));
        }

        if ($isProfessor) {
            $data = array_merge($data, $this->dadosDoProfessor($user));
        }

        return view('livewire.dashboard', $data);
    }

    private function dadosDoAluno(User $aluno): array
    {
        $turmaRecente = $aluno->turmasMatriculadas()
            ->orderByDesc('turma_aluno.data_matricula')
            ->first();

        $total = $turmaRecente?->totalAulas() ?? 0;
        $concluidas = $turmaRecente?->aulasConcluidasPor($aluno) ?? 0;

        return [
            'turmasDoAluno' => Turma::doAluno($aluno)->with('curso')->orderBy('nome')->get(),
            'aulasAoVivo' => AulaAoVivo::with('turma')->ativas()
                ->whereIn('turma_id', Turma::doAluno($aluno)->select('turmas.id'))
                ->orderByRaw("case status when 'ao_vivo' then 0 else 1 end")
                ->orderBy('inicio_em')
                ->limit(3)
                ->get(),
            'turmaRecente' => $turmaRecente,
            'continuar' => $turmaRecente
                ? $turmaRecente->proximoConteudoDisponivelPara($aluno)
                : ['status' => 'sem-turma', 'url' => null],
            'resumo' => [
                'total' => $total,
                'concluidas' => $concluidas,
                'restantes' => max(0, $total - $concluidas),
                'percentual' => $turmaRecente?->percentualConcluido($aluno) ?? 0.0,
            ],
            // Atividades Extras vira o banner de "bônus", então sai dos carrosséis.
            'secoes' => $turmaRecente
                ? collect($turmaRecente->vitrinePara($aluno))->reject(fn ($secao) => $secao['titulo'] === 'Atividades Extras')->values()->all()
                : [],
            'bonus' => $turmaRecente
                ? $turmaRecente->modulos()->where('categoria', 'extra')->pluck('nome')
                : collect(),
        ];
    }

    private function dadosDoProfessor(User $professor): array
    {
        $turmas = Turma::doProfessor($professor)
            ->with('curso')
            ->withCount('modulos')
            ->orderBy('nome')
            ->get()
            ->each(function (Turma $turma) {
                $turma->progressoAlunos = $turma->progressoDosAlunos();
                $turma->totalAulasNivel = $turma->totalAulas();
                $turma->progressoMedio = $turma->progressoAlunos->isEmpty()
                    ? 0.0
                    : round($turma->progressoAlunos->avg('percentual'), 1);
            });

        $limite = now()->subDays(7);

        $atencao = $turmas
            ->flatMap(fn (Turma $turma) => $turma->progressoAlunos->map(fn (array $linha) => $linha + ['turma' => $turma]))
            ->filter(fn (array $l) => $l['total'] > 0 && ($l['ultima_atividade'] === null || $l['ultima_atividade']->lt($limite)))
            ->sortBy(fn (array $l) => $l['ultima_atividade']?->timestamp ?? 0)
            ->take(6)
            ->values();

        $atividade = $turmas->isEmpty()
            ? collect()
            : AlunoProgresso::with(['aluno', 'conteudo.modulo'])
                ->whereHas('conteudo.modulo', fn ($q) => $q->whereIn('turma_id', $turmas->pluck('id')))
                ->latest('concluido_em')
                ->limit(8)
                ->get();

        $alunosIds = $turmas->flatMap(fn (Turma $t) => $t->progressoAlunos->pluck('aluno.id'))->unique();

        return [
            'turmasDoProfessor' => $turmas,
            'resumoProfessor' => [
                'turmas' => $turmas->count(),
                'alunos' => $alunosIds->count(),
                'aulas' => $turmas->sum('totalAulasNivel'),
                'progressoMedio' => $turmas->isEmpty() ? 0.0 : round($turmas->avg('progressoMedio'), 1),
            ],
            'alunosEmAtencao' => $atencao,
            'atividadeRecente' => $atividade,
        ];
    }
}
