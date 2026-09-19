<?php

namespace Tests\Feature\Academico;

use App\Livewire\Academico\Aluno\Aula;
use App\Models\Conteudo;
use App\Models\Curso;
use App\Models\Modulo;
use App\Models\Turma;
use App\Models\User;
use Database\Seeders\AcademicoPermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProgressoTest extends TestCase
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
        $this->turma->alunos()->attach($this->aluno->id, [
            'data_matricula' => now()->toDateString(),
            'status' => 'ativo',
        ]);
    }

    public function test_total_aulas_ignora_modulos_categoria_extra(): void
    {
        $moduloNivel = Modulo::factory()->create(['turma_id' => $this->turma->id]);
        Conteudo::factory()->count(3)->create(['modulo_id' => $moduloNivel->id]);

        $moduloExtra = Modulo::factory()->extra()->create(['turma_id' => $this->turma->id]);
        Conteudo::factory()->count(2)->create(['modulo_id' => $moduloExtra->id]);

        $this->assertSame(3, $this->turma->totalAulas());
    }

    public function test_aulas_concluidas_e_percentual_calculam_certo(): void
    {
        $modulo = Modulo::factory()->create(['turma_id' => $this->turma->id]);
        $conteudos = Conteudo::factory()->count(4)->create(['modulo_id' => $modulo->id]);

        $conteudos->take(1)->each(function (Conteudo $conteudo) {
            $conteudo->progressos()->create(['aluno_id' => $this->aluno->id, 'concluido_em' => now()]);
        });

        $this->assertSame(1, $this->turma->aulasConcluidasPor($this->aluno));
        $this->assertSame(25.0, $this->turma->percentualConcluido($this->aluno));
    }

    public function test_percentual_e_zero_quando_turma_nao_tem_aulas(): void
    {
        $this->assertSame(0, $this->turma->totalAulas());
        $this->assertSame(0.0, $this->turma->percentualConcluido($this->aluno));
    }

    public function test_marcar_e_desmarcar_conclusao_via_livewire(): void
    {
        $modulo = Modulo::factory()->create(['turma_id' => $this->turma->id]);
        $conteudo = Conteudo::factory()->liberaEm(0)->create(['modulo_id' => $modulo->id]);

        Livewire::actingAs($this->aluno)
            ->test(Aula::class, ['turma' => $this->turma, 'conteudo' => $conteudo])
            ->call('toggleConclusao');

        $this->assertTrue($conteudo->fresh()->concluidoPor($this->aluno));

        Livewire::actingAs($this->aluno)
            ->test(Aula::class, ['turma' => $this->turma, 'conteudo' => $conteudo])
            ->call('toggleConclusao');

        $this->assertFalse($conteudo->fresh()->concluidoPor($this->aluno));
    }

    public function test_nao_deixa_marcar_concluido_um_conteudo_bloqueado(): void
    {
        $modulo = Modulo::factory()->create(['turma_id' => $this->turma->id]);
        $conteudo = Conteudo::factory()->bloqueado()->create(['modulo_id' => $modulo->id]);

        Livewire::actingAs($this->aluno)
            ->test(Aula::class, ['turma' => $this->turma, 'conteudo' => $conteudo])
            ->call('toggleConclusao')
            ->assertForbidden();

        $this->assertFalse($conteudo->fresh()->concluidoPor($this->aluno));
    }

    public function test_continuar_estudando_pula_bloqueados_e_concluidos(): void
    {
        $modulo = Modulo::factory()->create(['turma_id' => $this->turma->id]);

        $bloqueado = Conteudo::factory()->bloqueado()->create(['modulo_id' => $modulo->id, 'ordem' => 0]);
        $concluido = Conteudo::factory()->liberaEm(0)->create(['modulo_id' => $modulo->id, 'ordem' => 1]);
        $proximo = Conteudo::factory()->liberaEm(0)->create(['modulo_id' => $modulo->id, 'ordem' => 2]);

        $concluido->progressos()->create(['aluno_id' => $this->aluno->id, 'concluido_em' => now()]);

        $resultado = $this->turma->proximoConteudoDisponivelPara($this->aluno);

        $this->assertSame('proximo', $resultado['status']);
        $this->assertSame(route('academico.minha-turma.aula', [$this->turma, $proximo]), $resultado['url']);
    }

    public function test_continuar_estudando_retorna_tudo_concluido_quando_nao_ha_mais_nada_pendente(): void
    {
        $modulo = Modulo::factory()->create(['turma_id' => $this->turma->id]);
        $conteudo = Conteudo::factory()->liberaEm(0)->create(['modulo_id' => $modulo->id]);
        $conteudo->progressos()->create(['aluno_id' => $this->aluno->id, 'concluido_em' => now()]);

        $resultado = $this->turma->proximoConteudoDisponivelPara($this->aluno);

        $this->assertSame('tudo-concluido', $resultado['status']);
    }

    public function test_progresso_e_isolado_por_turma(): void
    {
        $outroCurso = Curso::factory()->create();
        $outroProfessor = User::factory()->create();
        $outroProfessor->assignRole('professor');
        $outraTurma = Turma::factory()->create(['curso_id' => $outroCurso->id, 'professor_id' => $outroProfessor->id]);
        $outraTurma->alunos()->attach($this->aluno->id, ['data_matricula' => now()->toDateString(), 'status' => 'ativo']);

        $moduloA = Modulo::factory()->create(['turma_id' => $this->turma->id]);
        $conteudoA = Conteudo::factory()->count(2)->create(['modulo_id' => $moduloA->id])->first();
        $conteudoA->progressos()->create(['aluno_id' => $this->aluno->id, 'concluido_em' => now()]);

        $moduloB = Modulo::factory()->create(['turma_id' => $outraTurma->id]);
        Conteudo::factory()->count(2)->create(['modulo_id' => $moduloB->id]);

        $this->assertSame(50.0, $this->turma->percentualConcluido($this->aluno));
        $this->assertSame(0.0, $outraTurma->percentualConcluido($this->aluno));
    }
}
