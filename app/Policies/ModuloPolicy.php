<?php

namespace App\Policies;

use App\Models\Modulo;
use App\Models\User;

class ModuloPolicy
{
    public function view(User $user, Modulo $modulo): bool
    {
        return (new TurmaPolicy)->view($user, $modulo->turma);
    }

    public function manage(User $user, Modulo $modulo): bool
    {
        return (new TurmaPolicy)->manageConteudo($user, $modulo->turma);
    }
}
