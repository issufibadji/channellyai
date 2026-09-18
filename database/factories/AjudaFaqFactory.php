<?php

namespace Database\Factories;

use App\Models\AjudaFaq;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AjudaFaq>
 */
class AjudaFaqFactory extends Factory
{
    protected $model = AjudaFaq::class;

    public function definition(): array
    {
        return [
            'role' => 'aluno',
            'icone' => 'question-mark-circle',
            'pergunta' => fake()->sentence().'?',
            'resposta' => fake()->paragraph(),
            'ordem' => 0,
            'ativo' => true,
        ];
    }

    public function inativo(): static
    {
        return $this->state(fn (array $attributes) => [
            'ativo' => false,
        ]);
    }
}
