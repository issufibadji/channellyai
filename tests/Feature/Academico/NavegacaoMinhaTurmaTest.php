<?php

namespace Tests\Feature\Academico;

use App\Models\Curso;
use App\Models\Modulo;
use App\Models\Turma;
use App\Models\User;
use Database\Seeders\AcademicoPermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NavegacaoMinhaTurmaTest extends TestCase
{
    use RefreshDatabase;

    private Turma $turma;

    private User $alunoMatriculado;

    private User $alunoNaoMatriculado;

    private Modulo $modulo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->seed(AcademicoPermissionSeeder::class);

        $curso = Curso::factory()->create();
        $professor = User::factory()->create();
        $professor->assignRole('professor');
        $this->turma = Turma::factory()->create(['curso_id' => $curso->id, 'professor_id' => $professor->id]);

        $this->modulo = Modulo::factory()->create(['turma_id' => $this->turma->id]);

        $this->alunoMatriculado = User::factory()->create();
        $this->alunoMatriculado->assignRole('aluno');
        $this->turma->alunos()->attach($this->alunoMatriculado->id, [
            'data_matricula' => now()->toDateString(),
            'status' => 'ativo',
        ]);

        $this->alunoNaoMatriculado = User::factory()->create();
        $this->alunoNaoMatriculado->assignRole('aluno');
    }

    public function test_aluno_matriculado_acessa_as_3_rotas_normalmente(): void
    {
        $this->actingAs($this->alunoMatriculado)->get(route('academico.minha-turma.index'))->assertOk();
        $this->actingAs($this->alunoMatriculado)->get(route('academico.minha-turma.turma', $this->turma))->assertOk();
        $this->actingAs($this->alunoMatriculado)
            ->get(route('academico.minha-turma.modulo', [$this->turma, $this->modulo]))
            ->assertOk();
    }

    public function test_aluno_nao_matriculado_recebe_403_nas_3_rotas(): void
    {
        $this->actingAs($this->alunoNaoMatriculado)->get(route('academico.minha-turma.turma', $this->turma))->assertForbidden();
        $this->actingAs($this->alunoNaoMatriculado)
            ->get(route('academico.minha-turma.modulo', [$this->turma, $this->modulo]))
            ->assertForbidden();
    }

    public function test_abas_filtram_modulos_pelos_niveis_certos(): void
    {
        $moduloA1 = Modulo::factory()->create(['turma_id' => $this->turma->id, 'nivel' => 'A1', 'nome' => 'Modulo A1']);
        $moduloB1 = Modulo::factory()->create(['turma_id' => $this->turma->id, 'nivel' => 'B1', 'nome' => 'Modulo B1']);
        $moduloC1 = Modulo::factory()->create(['turma_id' => $this->turma->id, 'nivel' => 'C1', 'nome' => 'Modulo C1']);

        $basico = $this->actingAs($this->alunoMatriculado)->get(route('academico.minha-turma.turma', $this->turma));
        $basico->assertSee('Modulo A1');
        $basico->assertDontSee('Modulo B1');
        $basico->assertDontSee('Modulo C1');
    }

    public function test_modulos_extra_aparecem_separados_das_abas_de_nivel(): void
    {
        $extra = Modulo::factory()->extra()->create(['turma_id' => $this->turma->id, 'nome' => 'Clube de Conversação']);

        $response = $this->actingAs($this->alunoMatriculado)->get(route('academico.minha-turma.turma', $this->turma));

        $response->assertSee('Atividades Extras');
        $response->assertSee('Clube de Conversação');
    }
}
