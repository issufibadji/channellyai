<?php

namespace Tests\Feature\Academico;

use App\Models\Curso;
use App\Models\Turma;
use App\Models\User;
use Database\Seeders\AcademicoPermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MinhasTurmasTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->seed(AcademicoPermissionSeeder::class);
    }

    public function test_professor_only_sees_their_own_turmas(): void
    {
        $curso = Curso::factory()->create();

        $professorA = User::factory()->create(['name' => 'Professor A']);
        $professorA->assignRole('professor');

        $professorB = User::factory()->create(['name' => 'Professor B']);
        $professorB->assignRole('professor');

        Turma::factory()->create(['curso_id' => $curso->id, 'professor_id' => $professorA->id, 'nome' => 'Turma do A']);
        Turma::factory()->create(['curso_id' => $curso->id, 'professor_id' => $professorB->id, 'nome' => 'Turma do B']);

        $response = $this->actingAs($professorA)->get(route('academico.minhas-turmas.index'));

        $response->assertOk();
        $response->assertSee('Turma do A');
        $response->assertDontSee('Turma do B');
    }

    public function test_user_without_permission_gets_403(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('academico.minhas-turmas.index'))->assertForbidden();
    }
}
