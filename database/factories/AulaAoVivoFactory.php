<?php

namespace Database\Factories;

use App\Models\AulaAoVivo;
use App\Models\Turma;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AulaAoVivo>
 */
class AulaAoVivoFactory extends Factory
{
    protected $model = AulaAoVivo::class;

    public function definition(): array
    {
        return [
            'turma_id' => Turma::factory(),
            'titulo' => fake()->sentence(3),
            'descricao' => null,
            'inicio_em' => now()->addDay(),
            'duracao_minutos' => 60,
            'sala' => AulaAoVivo::gerarSala(),
            'status' => AulaAoVivo::AGENDADA,
        ];
    }

    public function aoVivo(): static
    {
        return $this->state(fn () => ['status' => AulaAoVivo::AO_VIVO, 'iniciada_em' => now()]);
    }

    public function encerrada(): static
    {
        return $this->state(fn () => [
            'status' => AulaAoVivo::ENCERRADA,
            'iniciada_em' => now()->subHours(2),
            'encerrada_em' => now()->subHour(),
        ]);
    }
}
