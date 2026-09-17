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

        return [
            'turmasDoAluno' => Turma::doAluno($aluno)->with('curso')->orderBy('nome')->get(),
            'turmaRecente' => $turmaRecente,
            'continuar' => $turmaRecente
                ? $turmaRecente->proximoConteudoDisponivelPara($aluno)
                : ['status' => 'sem-turma', 'url' => null],
        ];
    }
}
