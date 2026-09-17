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

    public function manageAlunos(User $user, Turma $turma): bool
    {
        return $user->hasRole('admin') || $turma->professor_id === $user->id;
    }
}
