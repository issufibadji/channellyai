<?php

namespace Tests\Feature\Academico;

use App\Models\Conteudo;
use App\Models\Curso;
use App\Models\Modulo;
use App\Models\Turma;
use App\Models\User;
use Database\Seeders\AcademicoPermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class DisponibilidadeConteudoTest extends TestCase
{
    use RefreshDatabase;

    private Turma $turma;

    private User $aluno;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->seed(AcademicoPermissionSeeder::class);

        $curso = Curso::factory()->create();
        $professor = User::factory()->create();
        $professor->assignRole('professor');

        $this->turma = Turma::factory()->create(['curso_id' => $curso->id, 'professor_id' => $professor->id]);

        $this->aluno = User::factory()->create();
        $this->aluno->assignRole('aluno');
    }

    private function matricular(Carbon $dataMatricula): void
    {
        $this->turma->alunos()->attach($this->aluno->id, [
            'data_matricula' => $dataMatricula->toDateString(),
            'status' => 'ativo',
        ]);
    }

    public function test_conteudo_com_dias_liberacao_zero_esta_disponivel_no_dia_da_matricula(): void
    {
        $this->matricular(now());

        $modulo = Modulo::factory()->create(['turma_id' => $this->turma->id]);
        $conteudo = Conteudo::factory()->liberaEm(0)->create(['modulo_id' => $modulo->id]);

        $this->assertTrue($conteudo->disponivelPara($this->aluno, $this->turma));
    }

    public function test_conteudo_com_prazo_de_5_dias_nao_disponivel_no_dia_3(): void
    {
        $this->matricular(now()->subDays(3));

        $modulo = Modulo::factory()->create(['turma_id' => $this->turma->id]);
        $conteudo = Conteudo::factory()->liberaEm(5)->create(['modulo_id' => $modulo->id]);

        $this->assertFalse($conteudo->disponivelPara($this->aluno, $this->turma));
        $this->assertSame(2, $conteudo->diasRestantesPara($this->aluno, $this->turma));
    }

    public function test_conteudo_com_prazo_de_5_dias_disponivel_no_dia_5(): void
    {
        $this->matricular(now()->subDays(5));

        $modulo = Modulo::factory()->create(['turma_id' => $this->turma->id]);
        $conteudo = Conteudo::factory()->liberaEm(5)->create(['modulo_id' => $modulo->id]);

        $this->assertTrue($conteudo->disponivelPara($this->aluno, $this->turma));
        $this->assertSame(0, $conteudo->diasRestantesPara($this->aluno, $this->turma));
    }

    public function test_conteudo_bloqueado_nunca_fica_disponivel_mesmo_com_prazo_vencido(): void
    {
        $this->matricular(now()->subDays(30));

        $modulo = Modulo::factory()->create(['turma_id' => $this->turma->id]);
        $conteudo = Conteudo::factory()->liberaEm(0)->bloqueado()->create(['modulo_id' => $modulo->id]);

        $this->assertFalse($conteudo->disponivelPara($this->aluno, $this->turma));
    }

    public function test_continuar_estudando_pula_conteudos_bloqueados_e_aponta_pro_primeiro_disponivel(): void
    {
        $this->matricular(now());

        $modulo = Modulo::factory()->create(['turma_id' => $this->turma->id, 'ordem' => 0]);

        $bloqueado = Conteudo::factory()->bloqueado()->create(['modulo_id' => $modulo->id, 'ordem' => 0]);
        $indisponivel = Conteudo::factory()->liberaEm(10)->create(['modulo_id' => $modulo->id, 'ordem' => 1]);
        $disponivel = Conteudo::factory()->liberaEm(0)->create(['modulo_id' => $modulo->id, 'ordem' => 2]);

        $response = $this->actingAs($this->aluno)->get(route('academico.minha-turma.index'));

        $response->assertOk();
        $response->assertSee(route('academico.minha-turma.modulo', [$this->turma, $modulo]));
    }
}
