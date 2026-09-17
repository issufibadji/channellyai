<?php

namespace Database\Factories;

use App\Models\Curso;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Curso>
 */
class CursoFactory extends Factory
{
    protected $model = Curso::class;

    public function definition(): array
    {
        return [
            'nome' => fake()->words(3, true),
            'tipo' => fake()->randomElement(['longo_prazo', 'curto_prazo']),
            'descricao' => fake()->sentence(),
            'ativo' => true,
        ];
    }
}
