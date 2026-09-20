<?php

namespace Tests\Feature;

use App\Models\Conteudo;
use App\Models\Curso;
use App\Models\Modulo;
use App\Models\Turma;
use App\Models\User;
use Database\Seeders\AcademicoPermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardAlunoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->seed(AcademicoPermissionSeeder::class);
    }

    public function test_dashboard_do_aluno_mostra_o_progresso_da_turma_certa(): void
    {
        $curso = Curso::factory()->create();
        $professor = User::factory()->create();
        $professor->assignRole('professor');
        $turma = Turma::factory()->create(['curso_id' => $curso->id, 'professor_id' => $professor->id, 'nome' => 'Inglês Geral']);

        $aluno = User::factory()->create();
        $aluno->assignRole('aluno');
        $turma->alunos()->attach($aluno->id, ['data_matricula' => now()->toDateString(), 'status' => 'ativo']);

        $modulo = Modulo::factory()->create(['turma_id' => $turma->id]);
        $conteudos = Conteudo::factory()->count(2)->liberaEm(0)->create(['modulo_id' => $modulo->id]);
        $conteudos->first()->progressos()->create(['aluno_id' => $aluno->id, 'concluido_em' => now()]);

        $response = $this->actingAs($aluno)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Inglês Geral');
        $response->assertSee('50%');
        $response->assertSee('1 / 2 aulas');
    }

    public function test_dashboard_de_admin_continua_igual(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Sua função');
        $response->assertSee('Membro desde');
        $response->assertDontSee('Seu progresso');
    }

    public function test_dashboard_de_professor_mostra_a_area_do_professor(): void
    {
        $professor = User::factory()->create();
        $professor->assignRole('professor');

        $response = $this->actingAs($professor)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Área do Professor');
        $response->assertDontSee('Seu progresso');
        $response->assertDontSee('Sua função');
    }
}
