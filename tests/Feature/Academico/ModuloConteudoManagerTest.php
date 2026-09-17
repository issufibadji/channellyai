<?php

namespace Tests\Feature\Academico;

use App\Livewire\Academico\ModuloConteudoManager;
use App\Models\Curso;
use App\Models\Modulo;
use App\Models\Turma;
use App\Models\User;
use Database\Seeders\AcademicoPermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ModuloConteudoManagerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->seed(AcademicoPermissionSeeder::class);
    }

    private function turmaDoProfessor(): array
    {
        $curso = Curso::factory()->create();
        $professor = User::factory()->create();
        $professor->assignRole('professor');
        $turma = Turma::factory()->create(['curso_id' => $curso->id, 'professor_id' => $professor->id]);

        return [$professor, $turma];
    }

    public function test_professor_can_create_a_modulo_for_their_own_turma(): void
    {
        [$professor, $turma] = $this->turmaDoProfessor();

        Livewire::actingAs($professor)
            ->test(ModuloConteudoManager::class, ['turma' => $turma])
            ->set('moduloNome', 'Módulo 1')
            ->set('nivel', 'A1')
            ->call('saveModulo');

        $this->assertDatabaseHas('modulos', ['turma_id' => $turma->id, 'nome' => 'Módulo 1']);
    }

    public function test_professor_can_create_conteudo_inside_a_modulo(): void
    {
        [$professor, $turma] = $this->turmaDoProfessor();
        $modulo = Modulo::factory()->create(['turma_id' => $turma->id]);

        Livewire::actingAs($professor)
            ->test(ModuloConteudoManager::class, ['turma' => $turma])
            ->call('createConteudo', $modulo->id)
            ->set('titulo', 'Aula 1')
            ->set('tipo', 'video')
            ->call('saveConteudo');

        $this->assertDatabaseHas('conteudos', ['modulo_id' => $modulo->id, 'titulo' => 'Aula 1']);
    }

    public function test_another_professor_cannot_mount_the_component_for_a_turma_that_is_not_theirs(): void
    {
        [, $turma] = $this->turmaDoProfessor();

        $outroProfessor = User::factory()->create();
        $outroProfessor->assignRole('professor');

        $this->actingAs($outroProfessor)
            ->get(route('academico.turmas.conteudo', $turma))
            ->assertForbidden();
    }

    public function test_professor_can_create_a_modulo_categoria_extra_without_a_nivel(): void
    {
        [$professor, $turma] = $this->turmaDoProfessor();

        Livewire::actingAs($professor)
            ->test(ModuloConteudoManager::class, ['turma' => $turma])
            ->set('moduloNome', 'Clube de Conversação')
            ->set('categoria', 'extra')
            ->call('saveModulo');

        $this->assertDatabaseHas('modulos', [
            'turma_id' => $turma->id,
            'nome' => 'Clube de Conversação',
            'categoria' => 'extra',
            'nivel' => null,
        ]);
    }

    public function test_saving_a_modulo_categoria_nivel_requires_a_nivel(): void
    {
        [$professor, $turma] = $this->turmaDoProfessor();

        Livewire::actingAs($professor)
            ->test(ModuloConteudoManager::class, ['turma' => $turma])
            ->set('moduloNome', 'Módulo sem nível')
            ->set('categoria', 'nivel')
            ->set('nivel', '')
            ->call('saveModulo')
            ->assertHasErrors('nivel');
    }

    public function test_professor_can_set_dias_liberacao_and_bloqueado_on_a_conteudo(): void
    {
        [$professor, $turma] = $this->turmaDoProfessor();
        $modulo = Modulo::factory()->create(['turma_id' => $turma->id]);

        Livewire::actingAs($professor)
            ->test(ModuloConteudoManager::class, ['turma' => $turma])
            ->call('createConteudo', $modulo->id)
            ->set('titulo', 'Aula bônus')
            ->set('tipo', 'video')
            ->set('diasLiberacao', 7)
            ->set('bloqueado', true)
            ->call('saveConteudo');

        $this->assertDatabaseHas('conteudos', [
            'modulo_id' => $modulo->id,
            'titulo' => 'Aula bônus',
            'dias_liberacao' => 7,
            'bloqueado' => true,
        ]);
    }
}
