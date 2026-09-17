<?php

namespace Database\Factories;

use App\Models\Curso;
use App\Models\Turma;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Turma>
 */
class TurmaFactory extends Factory
{
    protected $model = Turma::class;

    public function definition(): array
    {
        return [
            'curso_id' => Curso::factory(),
            'professor_id' => User::factory(),
            'nome' => 'Turma '.fake()->unique()->numberBetween(1, 999),
            'data_inicio' => fake()->date(),
            'data_fim' => null,
            'ativo' => true,
        ];
    }
}
