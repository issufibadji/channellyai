<?php

namespace App\Policies;

use App\Models\Turma;
use App\Models\User;

class TurmaPolicy
{
    public function view(User $user, Turma $turma): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        if ($user->hasRole('professor')) {
            return $turma->professor_id === $user->id;
        }

        if ($user->hasRole('aluno')) {
            return $turma->alunos()->where('users.id', $user->id)->exists();
        }

        return false;
    }

    /**
     * Gerenciar módulos/conteúdo (material) da turma — é o professor
     * responsável por ela, não uma permissão global.
     */
    public function manageConteudo(User $user, Turma $turma): bool
    {
        return $user->hasRole('admin') || $turma->professor_id === $user->id;
    }

    /**
     * Gerenciar matrícula (matricular/desmatricular alunos) — capacidade
     * global de quem tem a permissão manage-matriculas (manager), não
     * depende de ser o professor responsável pela turma.
     */
    public function manageMatricula(User $user, Turma $turma): bool
    {
        return $user->hasRole('admin') || $user->can('manage-matriculas');
    }
}
