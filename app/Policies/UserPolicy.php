<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Papéis que um manager pode gerenciar — nunca admin ou outro manager.
     * Admin passa por tudo via Gate::before, então não precisa aparecer aqui.
     */
    public const PAPEIS_GERENCIAVEIS_POR_MANAGER = ['professor', 'aluno', 'operator'];

    public function view(User $user, User $alvo): bool
    {
        return $this->podeGerenciar($user, $alvo);
    }

    public function create(User $user): bool
    {
        return $user->hasRole('manager');
    }

    public function update(User $user, User $alvo): bool
    {
        return $this->podeGerenciar($user, $alvo);
    }

    public function delete(User $user, User $alvo): bool
    {
        return $this->podeGerenciar($user, $alvo);
    }

    private function podeGerenciar(User $user, User $alvo): bool
    {
        if (! $user->hasRole('manager')) {
            return false;
        }

        return $alvo->roles->isEmpty()
            || $alvo->roles->pluck('name')->every(
                fn (string $role) => in_array($role, self::PAPEIS_GERENCIAVEIS_POR_MANAGER, true),
            );
    }
}
