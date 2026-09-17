<?php

namespace Tests\Feature\Academico;

use App\Models\Curso;
use App\Models\Turma;
use App\Models\User;
use Database\Seeders\AcademicoPermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AlunoManagerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->seed(AcademicoPermissionSeeder::class);
    }

    public function test_professor_only_sees_students_enrolled_in_their_own_turmas(): void
    {
        $curso = Curso::factory()->create();

        $professorA = User::factory()->create();
        $professorA->assignRole('professor');
        $turmaA = Turma::factory()->create(['curso_id' => $curso->id, 'professor_id' => $professorA->id]);

        $professorB = User::factory()->create();
        $professorB->assignRole('professor');
        $turmaB = Turma::factory()->create(['curso_id' => $curso->id, 'professor_id' => $professorB->id]);

        $alunoA = User::factory()->create(['name' => 'Aluno da Turma A']);
        $alunoA->assignRole('aluno');
        $turmaA->alunos()->attach($alunoA->id, ['data_matricula' => now()->toDateString(), 'status' => 'ativo']);

        $alunoB = User::factory()->create(['name' => 'Aluno da Turma B']);
        $alunoB->assignRole('aluno');
        $turmaB->alunos()->attach($alunoB->id, ['data_matricula' => now()->toDateString(), 'status' => 'ativo']);

        $response = $this->actingAs($professorA)->get(route('academico.meus-alunos.index'));

        $response->assertOk();
        $response->assertSee('Aluno da Turma A');
        $response->assertDontSee('Aluno da Turma B');
    }
}
