<?php

namespace Tests\Concerns;

use App\Models\Estabelecimento;
use App\Models\User;

trait WithEstabelecimentoAtual
{
    protected function criarEstabelecimento(array $atributos = []): Estabelecimento
    {
        return Estabelecimento::create(array_merge([
            'nome' => 'Estabelecimento Teste',
            'slug' => 'estabelecimento-teste-'.uniqid(),
            'tipo_negocio' => 'consultorio',
            'ativo' => true,
        ], $atributos));
    }

    protected function definirEstabelecimentoAtual(Estabelecimento $estabelecimento): void
    {
        session(['current_estabelecimento_id' => $estabelecimento->id]);
    }

    protected function vincularUsuario(User $user, Estabelecimento $estabelecimento): void
    {
        $user->estabelecimentos()->syncWithoutDetaching([$estabelecimento->id]);
    }
}
