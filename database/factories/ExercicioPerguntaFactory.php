<?php

namespace Database\Factories;

use App\Models\Conteudo;
use App\Models\ExercicioPergunta;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExercicioPergunta>
 */
class ExercicioPerguntaFactory extends Factory
{
    protected $model = ExercicioPergunta::class;

    public function definition(): array
    {
        return [
            'conteudo_id' => Conteudo::factory(),
            'enunciado' => fake()->sentence().'?',
            'ordem' => 0,
        ];
    }
}
