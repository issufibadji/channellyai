<?php

namespace Database\Factories;

use App\Models\Conteudo;
use App\Models\Modulo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Conteudo>
 */
class ConteudoFactory extends Factory
{
    protected $model = Conteudo::class;

    public function definition(): array
    {
        return [
            'modulo_id' => Modulo::factory(),
            'titulo' => fake()->sentence(3),
            'tipo' => fake()->randomElement(['video', 'pdf', 'texto', 'exercicio', 'link']),
            'corpo' => fake()->paragraph(),
            'ordem' => 0,
            'dias_liberacao' => 0,
            'bloqueado' => false,
        ];
    }

    public function bloqueado(): static
    {
        return $this->state(fn (array $attributes) => [
            'bloqueado' => true,
        ]);
    }

    public function liberaEm(int $dias): static
    {
        return $this->state(fn (array $attributes) => [
            'dias_liberacao' => $dias,
        ]);
    }
}
