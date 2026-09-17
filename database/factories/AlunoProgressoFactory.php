<?php

namespace Database\Factories;

use App\Models\AlunoProgresso;
use App\Models\Conteudo;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AlunoProgresso>
 */
class AlunoProgressoFactory extends Factory
{
    protected $model = AlunoProgresso::class;

    public function definition(): array
    {
        return [
            'aluno_id' => User::factory(),
            'conteudo_id' => Conteudo::factory(),
            'concluido_em' => now(),
        ];
    }
}
