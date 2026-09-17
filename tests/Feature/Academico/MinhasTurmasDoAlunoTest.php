<?php

namespace Tests\Feature\Academico;

use App\Models\Curso;
use App\Models\Turma;
use App\Models\User;
use Database\Seeders\AcademicoPermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MinhasTurmasDoAlunoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->seed(AcademicoPermissionSeeder::class);
    }

    public function test_aluno_only_sees_turmas_they_are_enrolled_in(): void
    {
        $curso = Curso::factory()->create();
        $professor = User::factory()->create();
        $professor->assignRole('professor');

        $turmaMatriculada = Turma::factory()->create(['curso_id' => $curso->id, 'professor_id' => $professor->id, 'nome' => 'Turma Matriculada']);
        $turmaNaoMatriculada = Turma::factory()->create(['curso_id' => $curso->id, 'professor_id' => $professor->id, 'nome' => 'Turma Não Matriculada']);

        $aluno = User::factory()->create();
        $aluno->assignRole('aluno');
        $turmaMatriculada->alunos()->attach($aluno->id, ['data_matricula' => now()->toDateString(), 'status' => 'ativo']);

        $response = $this->actingAs($aluno)->get(route('academico.minha-turma.index'));

        $response->assertOk();
        $response->assertSee('Turma Matriculada');
        $response->assertDontSee('Turma Não Matriculada');
    }

    public function test_aluno_enrolled_in_two_turmas_sees_both(): void
    {
        $curso = Curso::factory()->create();
        $professor = User::factory()->create();
        $professor->assignRole('professor');

        $turma1 = Turma::factory()->create(['curso_id' => $curso->id, 'professor_id' => $professor->id, 'nome' => 'Inglês Geral']);
        $turma2 = Turma::factory()->create(['curso_id' => $curso->id, 'professor_id' => $professor->id, 'nome' => 'Business English']);

        $aluno = User::factory()->create();
        $aluno->assignRole('aluno');
        $turma1->alunos()->attach($aluno->id, ['data_matricula' => now()->toDateString(), 'status' => 'ativo']);
        $turma2->alunos()->attach($aluno->id, ['data_matricula' => now()->toDateString(), 'status' => 'ativo']);

        $response = $this->actingAs($aluno)->get(route('academico.minha-turma.index'));

        $response->assertSee('Inglês Geral');
        $response->assertSee('Business English');
    }
}
