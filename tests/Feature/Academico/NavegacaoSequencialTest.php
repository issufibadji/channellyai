<?php

namespace Tests\Feature\Academico;

use App\Models\Conteudo;
use App\Models\Curso;
use App\Models\Modulo;
use App\Models\Turma;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NavegacaoSequencialTest extends TestCase
{
    use RefreshDatabase;

    public function test_navega_dentro_da_trilha_por_nivel(): void
    {
        $curso = Curso::factory()->create();
        $professor = User::factory()->create();
        $turma = Turma::factory()->create(['curso_id' => $curso->id, 'professor_id' => $professor->id]);

        $moduloA1 = Modulo::factory()->create(['turma_id' => $turma->id, 'categoria' => 'nivel', 'nivel' => 'A1', 'ordem' => 1]);
        $moduloB1 = Modulo::factory()->create(['turma_id' => $turma->id, 'categoria' => 'nivel', 'nivel' => 'B1', 'ordem' => 2]);

        $aula1 = Conteudo::factory()->create(['modulo_id' => $moduloA1->id, 'ordem' => 1]);
        $aula2 = Conteudo::factory()->create(['modulo_id' => $moduloA1->id, 'ordem' => 2]);
        $aula3 = Conteudo::factory()->create(['modulo_id' => $moduloB1->id, 'ordem' => 1]);

        $vizinhosAula2 = $turma->vizinhosDoConteudo($aula2);
        $this->assertSame($aula1->id, $vizinhosAula2['anterior']->id);
        $this->assertSame($aula3->id, $vizinhosAula2['proximo']->id);
    }

    public function test_nao_atravessa_para_dentro_de_atividades_extras(): void
    {
        $curso = Curso::factory()->create();
        $professor = User::factory()->create();
        $turma = Turma::factory()->create(['curso_id' => $curso->id, 'professor_id' => $professor->id]);

        $moduloA1 = Modulo::factory()->create(['turma_id' => $turma->id, 'categoria' => 'nivel', 'nivel' => 'A1', 'ordem' => 1]);
        $moduloExtra = Modulo::factory()->extra()->create(['turma_id' => $turma->id, 'ordem' => 2]);

        $aulaNivel = Conteudo::factory()->create(['modulo_id' => $moduloA1->id, 'ordem' => 1]);
        $aulaExtra = Conteudo::factory()->create(['modulo_id' => $moduloExtra->id, 'ordem' => 1]);

        // A única aula de nível não tem vizinhos, mesmo tendo uma aula
        // extra "depois" dela na tabela — extra não entra na trilha.
        $vizinhos = $turma->vizinhosDoConteudo($aulaNivel);
        $this->assertNull($vizinhos['anterior']);
        $this->assertNull($vizinhos['proximo']);

        // A aula extra em si nem aparece na sequência.
        $vizinhosExtra = $turma->vizinhosDoConteudo($aulaExtra);
        $this->assertNull($vizinhosExtra['anterior']);
        $this->assertNull($vizinhosExtra['proximo']);
    }

    public function test_primeira_aula_nao_tem_anterior_ultima_nao_tem_proxima(): void
    {
        $curso = Curso::factory()->create();
        $professor = User::factory()->create();
        $turma = Turma::factory()->create(['curso_id' => $curso->id, 'professor_id' => $professor->id]);

        $modulo = Modulo::factory()->create(['turma_id' => $turma->id, 'categoria' => 'nivel', 'nivel' => 'A1', 'ordem' => 1]);

        $primeira = Conteudo::factory()->create(['modulo_id' => $modulo->id, 'ordem' => 1]);
        $ultima = Conteudo::factory()->create(['modulo_id' => $modulo->id, 'ordem' => 2]);

        $vizinhosPrimeira = $turma->vizinhosDoConteudo($primeira);
        $this->assertNull($vizinhosPrimeira['anterior']);
        $this->assertSame($ultima->id, $vizinhosPrimeira['proximo']->id);

        $vizinhosUltima = $turma->vizinhosDoConteudo($ultima);
        $this->assertSame($primeira->id, $vizinhosUltima['anterior']->id);
        $this->assertNull($vizinhosUltima['proximo']);
    }
}
