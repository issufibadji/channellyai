<?php

namespace Tests\Feature\Estabelecimento;

use App\Models\Estabelecimento;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SlugTest extends TestCase
{
    use RefreshDatabase;

    public function test_slug_deve_ser_unico_no_banco(): void
    {
        Estabelecimento::create(['nome' => 'A', 'slug' => 'duplicado', 'tipo_negocio' => 'outro']);

        $this->expectException(QueryException::class);

        Estabelecimento::create(['nome' => 'B', 'slug' => 'duplicado', 'tipo_negocio' => 'outro']);
    }

    public function test_slug_aceita_minusculas_numeros_e_hifen(): void
    {
        $estabelecimento = Estabelecimento::create([
            'nome' => 'Barbearia 2',
            'slug' => 'barbearia-2-centro',
            'tipo_negocio' => 'barbearia',
        ]);

        $this->assertSame('barbearia-2-centro', $estabelecimento->slug);
    }
}
