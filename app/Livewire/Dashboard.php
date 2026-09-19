<?php

namespace App\Livewire;

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

        $data = [
            'recentNotifications' => $user->notifications()->latest()->limit(5)->get(),
            'unreadCount' => $user->unreadNotifications()->count(),
            'roleName' => $user->getRoleNames()->first() ?? 'Sem função',
            'memberSince' => $user->created_at,
            'isAluno' => $isAluno,
        ];

        if ($isAluno) {
            $data = array_merge($data, $this->dadosDoAluno($user));
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
}
