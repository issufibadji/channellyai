<?php

namespace Database\Factories;

use App\Models\ExercicioOpcao;
use App\Models\ExercicioPergunta;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExercicioOpcao>
 */
class ExercicioOpcaoFactory extends Factory
{
    protected $model = ExercicioOpcao::class;

    public function definition(): array
    {
        return [
            'pergunta_id' => ExercicioPergunta::factory(),
            'texto' => fake()->words(3, true),
            'correta' => false,
            'ordem' => 0,
        ];
    }

    public function correta(): static
    {
        return $this->state(fn (array $attributes) => [
            'correta' => true,
        ]);
    }

    public function incorreta(): static
    {
        return $this->state(fn (array $attributes) => [
            'correta' => false,
        ]);
    }
}
