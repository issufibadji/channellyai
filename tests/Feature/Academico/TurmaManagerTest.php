<?php

namespace Tests\Feature\Academico;

use App\Livewire\Academico\TurmaManager;
use App\Models\Curso;
use App\Models\User;
use Database\Seeders\AcademicoPermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TurmaManagerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->seed(AcademicoPermissionSeeder::class);
    }

    public function test_user_without_permission_gets_403(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('academico.turmas.index'))->assertForbidden();
    }

    public function test_admin_can_create_a_turma_with_a_professor(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $curso = Curso::factory()->create();
        $professor = User::factory()->create();
        $professor->assignRole('professor');

        Livewire::actingAs($admin)
            ->test(TurmaManager::class)
            ->set('cursoId', $curso->id)
            ->set('professorId', $professor->id)
            ->set('nome', 'Turma Manhã')
            ->call('save');

        $this->assertDatabaseHas('turmas', [
            'nome' => 'Turma Manhã',
            'curso_id' => $curso->id,
            'professor_id' => $professor->id,
        ]);
    }
}
