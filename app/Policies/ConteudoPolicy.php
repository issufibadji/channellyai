<?php

namespace App\Policies;

use App\Models\Conteudo;
use App\Models\User;

class ConteudoPolicy
{
    public function view(User $user, Conteudo $conteudo): bool
    {
        return (new TurmaPolicy())->view($user, $conteudo->modulo->turma);
    }

    public function manage(User $user, Conteudo $conteudo): bool
    {
        return (new TurmaPolicy())->manageAlunos($user, $conteudo->modulo->turma);
    }
}
