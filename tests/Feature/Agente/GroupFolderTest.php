<?php

namespace Tests\Feature\Agente;

use App\Models\Agente;
use App\Models\Estabelecimento;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GroupFolderTest extends TestCase
{
    use RefreshDatabase;

    public function test_group_folder_padrao_e_o_slug_do_estabelecimento(): void
    {
        $estabelecimento = Estabelecimento::create(['nome' => 'Barbearia do Zé', 'slug' => 'barbearia-do-ze', 'tipo_negocio' => 'barbearia']);

        $agente = Agente::create(['estabelecimento_id' => $estabelecimento->id, 'nome' => 'Agente Zé']);

        $this->assertSame('barbearia-do-ze', $agente->group_folder);
    }

    public function test_group_folder_deve_ser_unico_entre_agentes(): void
    {
        $estA = Estabelecimento::create(['nome' => 'A', 'slug' => 'estabelecimento-a', 'tipo_negocio' => 'outro']);
        $estB = Estabelecimento::create(['nome' => 'B', 'slug' => 'estabelecimento-b', 'tipo_negocio' => 'outro']);

        Agente::create(['estabelecimento_id' => $estA->id, 'nome' => 'Agente A', 'group_folder' => 'pasta-compartilhada']);

        $this->expectException(QueryException::class);

        Agente::create(['estabelecimento_id' => $estB->id, 'nome' => 'Agente B', 'group_folder' => 'pasta-compartilhada']);
    }
}
