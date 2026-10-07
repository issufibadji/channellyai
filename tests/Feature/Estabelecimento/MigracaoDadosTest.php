<?php

namespace Tests\Feature\Estabelecimento;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MigracaoDadosTest extends TestCase
{
    use RefreshDatabase;

    public function test_estabelecimento_padrao_consultorio_beta_foi_criado_pela_migracao(): void
    {
        $this->assertDatabaseHas('estabelecimentos', [
            'slug' => 'consultorio-beta',
            'nome' => 'Consultório Beta',
            'tipo_negocio' => 'consultorio',
        ]);
    }
}
