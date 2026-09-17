<?php

namespace Tests\Feature\Academico;

use App\Livewire\Academico\CursoManager;
use App\Models\Curso;
use App\Models\User;
use Database\Seeders\AcademicoPermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CursoManagerTest extends TestCase
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

        $this->actingAs($user)->get(route('academico.cursos.index'))->assertForbidden();
    }

    public function test_admin_can_create_a_curso(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        Livewire::actingAs($admin)
            ->test(CursoManager::class)
            ->set('nome', 'Inglês Geral')
            ->set('tipo', 'longo_prazo')
            ->call('save');

        $this->assertDatabaseHas('cursos', ['nome' => 'Inglês Geral', 'tipo' => 'longo_prazo']);
    }

    public function test_admin_can_delete_a_curso(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $curso = Curso::factory()->create();

        Livewire::actingAs($admin)
            ->test(CursoManager::class)
            ->call('delete', $curso->id);

        $this->assertDatabaseMissing('cursos', ['id' => $curso->id]);
    }
}
