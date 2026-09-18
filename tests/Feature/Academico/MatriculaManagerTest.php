<?php

namespace Tests\Feature\Academico;

use App\Livewire\Academico\MatriculaManager;
use App\Models\Curso;
use App\Models\Turma;
use App\Models\User;
use Database\Seeders\AcademicoPermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MatriculaManagerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->seed(AcademicoPermissionSeeder::class);
    }

    public function test_manager_can_enroll_a_student_in_any_turma(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('manager');

        $curso = Curso::factory()->create();
        $professor = User::factory()->create();
        $professor->assignRole('professor');
        $turma = Turma::factory()->create(['curso_id' => $curso->id, 'professor_id' => $professor->id]);

        $aluno = User::factory()->create();
        $aluno->assignRole('aluno');

        Livewire::actingAs($manager)
            ->test(MatriculaManager::class, ['turma' => $turma])
            ->set('alunoId', $aluno->id)
            ->call('matricular');

        $this->assertDatabaseHas('turma_aluno', ['turma_id' => $turma->id, 'aluno_id' => $aluno->id]);
    }

    public function test_manager_can_unenroll_a_student(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('manager');

        $curso = Curso::factory()->create();
        $professor = User::factory()->create();
        $professor->assignRole('professor');
        $turma = Turma::factory()->create(['curso_id' => $curso->id, 'professor_id' => $professor->id]);

        $aluno = User::factory()->create();
        $aluno->assignRole('aluno');
        $turma->alunos()->attach($aluno->id, ['data_matricula' => now()->toDateString(), 'status' => 'ativo']);

        Livewire::actingAs($manager)
            ->test(MatriculaManager::class, ['turma' => $turma])
            ->call('desmatricular', $aluno->id);

        $this->assertDatabaseMissing('turma_aluno', ['turma_id' => $turma->id, 'aluno_id' => $aluno->id]);
    }

    public function test_enrolling_the_same_student_twice_does_not_duplicate_the_row(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('manager');

        $curso = Curso::factory()->create();
        $professor = User::factory()->create();
        $professor->assignRole('professor');
        $turma = Turma::factory()->create(['curso_id' => $curso->id, 'professor_id' => $professor->id]);

        $aluno = User::factory()->create();
        $aluno->assignRole('aluno');

        $component = Livewire::actingAs($manager)->test(MatriculaManager::class, ['turma' => $turma]);
        $component->set('alunoId', $aluno->id)->call('matricular');
        $component->set('alunoId', $aluno->id)->call('matricular');

        $this->assertSame(1, $turma->alunos()->where('users.id', $aluno->id)->count());
    }

    public function test_a_plain_professor_cannot_access_the_matricula_screen_even_for_their_own_turma(): void
    {
        $curso = Curso::factory()->create();
        $professor = User::factory()->create();
        $professor->assignRole('professor');
        $turma = Turma::factory()->create(['curso_id' => $curso->id, 'professor_id' => $professor->id]);

        $this->actingAs($professor)
            ->get(route('academico.turmas.matricula', $turma))
            ->assertForbidden();
    }
}
