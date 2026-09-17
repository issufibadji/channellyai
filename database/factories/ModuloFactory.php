<?php

namespace Database\Factories;

use App\Models\Modulo;
use App\Models\Turma;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Modulo>
 */
class ModuloFactory extends Factory
{
    protected $model = Modulo::class;

    public function definition(): array
    {
        return [
            'turma_id' => Turma::factory(),
            'nome' => fake()->words(2, true),
            'nivel' => fake()->randomElement(['A1', 'A2', 'B1', 'B2', 'C1', 'C2']),
            'ordem' => 0,
        ];
    }
}
