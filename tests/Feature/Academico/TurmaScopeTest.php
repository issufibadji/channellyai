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
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class TurmaScopeTest extends TestCase
{
    use RefreshDatabase;

    private Turma $turmaA;

    private Turma $turmaB;

    private User $professorA;

    private User $professorB;

    private User $alunoA;

    private User $alunoB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->seed(AcademicoPermissionSeeder::class);

        $curso = Curso::factory()->create();

        $this->professorA = User::factory()->create();
        $this->professorA->assignRole('professor');

        $this->professorB = User::factory()->create();
        $this->professorB->assignRole('professor');

        $this->turmaA = Turma::factory()->create(['curso_id' => $curso->id, 'professor_id' => $this->professorA->id]);
        $this->turmaB = Turma::factory()->create(['curso_id' => $curso->id, 'professor_id' => $this->professorB->id]);

        $this->alunoA = User::factory()->create();
        $this->alunoA->assignRole('aluno');
        $this->turmaA->alunos()->attach($this->alunoA->id, ['data_matricula' => now()->toDateString(), 'status' => 'ativo']);

        $this->alunoB = User::factory()->create();
        $this->alunoB->assignRole('aluno');
        $this->turmaB->alunos()->attach($this->alunoB->id, ['data_matricula' => now()->toDateString(), 'status' => 'ativo']);
    }

    public function test_scope_do_professor_only_returns_the_professors_own_turmas(): void
    {
        $turmas = Turma::doProfessor($this->professorA)->get();

        $this->assertTrue($turmas->contains($this->turmaA));
        $this->assertFalse($turmas->contains($this->turmaB));
    }

    public function test_scope_do_aluno_only_returns_turmas_the_student_is_enrolled_in(): void
    {
        $turmas = Turma::doAluno($this->alunoA)->get();

        $this->assertTrue($turmas->contains($this->turmaA));
        $this->assertFalse($turmas->contains($this->turmaB));
    }

    public function test_a_student_enrolled_in_two_turmas_sees_both(): void
    {
        $this->turmaB->alunos()->attach($this->alunoA->id, ['data_matricula' => now()->toDateString(), 'status' => 'ativo']);

        $turmas = Turma::doAluno($this->alunoA)->get();

        $this->assertCount(2, $turmas);
    }

    public function test_policy_denies_a_professor_from_viewing_another_professors_turma(): void
    {
        $this->assertTrue(Gate::forUser($this->professorA)->allows('view', $this->turmaA));
        $this->assertFalse(Gate::forUser($this->professorB)->allows('view', $this->turmaA));
    }

    public function test_policy_denies_a_professor_from_managing_another_professors_turma(): void
    {
        $this->assertTrue(Gate::forUser($this->professorA)->allows('manageConteudo', $this->turmaA));
        $this->assertFalse(Gate::forUser($this->professorB)->allows('manageConteudo', $this->turmaA));
    }

    public function test_policy_denies_a_student_from_viewing_a_turma_they_are_not_enrolled_in(): void
    {
        $this->assertTrue(Gate::forUser($this->alunoA)->allows('view', $this->turmaA));
        $this->assertFalse(Gate::forUser($this->alunoB)->allows('view', $this->turmaA));
    }

    public function test_admin_bypasses_all_turma_policy_checks(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->assertTrue(Gate::forUser($admin)->allows('view', $this->turmaA));
        $this->assertTrue(Gate::forUser($admin)->allows('manageConteudo', $this->turmaB));
    }

    public function test_professor_gets_403_accessing_the_conteudo_screen_of_another_professors_turma(): void
    {
        $this->actingAs($this->professorA)
            ->get(route('academico.turmas.conteudo', $this->turmaA))
            ->assertOk();

        $this->actingAs($this->professorB)
            ->get(route('academico.turmas.conteudo', $this->turmaA))
            ->assertForbidden();
    }

    public function test_professor_gets_403_accessing_the_matricula_screen_of_another_professors_turma(): void
    {
        $this->actingAs($this->professorB)
            ->get(route('academico.turmas.matricula', $this->turmaA))
            ->assertForbidden();
    }

    public function test_modulo_and_conteudo_policies_delegate_to_the_parent_turma(): void
    {
        $modulo = Modulo::factory()->create(['turma_id' => $this->turmaA->id]);
        $conteudo = Conteudo::factory()->create(['modulo_id' => $modulo->id]);

        $this->assertTrue(Gate::forUser($this->professorA)->allows('manage', $modulo));
        $this->assertFalse(Gate::forUser($this->professorB)->allows('manage', $modulo));

        $this->assertTrue(Gate::forUser($this->professorA)->allows('manage', $conteudo));
        $this->assertFalse(Gate::forUser($this->professorB)->allows('manage', $conteudo));
    }
}
