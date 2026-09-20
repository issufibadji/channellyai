<?php

namespace Tests\Feature;

use App\Livewire\Dashboard;
use App\Models\AlunoProgresso;
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

class DashboardProfessorTest extends TestCase
{
    use RefreshDatabase;

    private User $professor;

    private Turma $turma;

    private Modulo $modulo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->seed(AcademicoPermissionSeeder::class);

        $this->professor = User::factory()->create(['name' => 'Paula Professora']);
        $this->professor->assignRole('professor');

        $this->turma = Turma::factory()->create([
            'curso_id' => Curso::factory()->create(['nome' => 'Inglês Geral'])->id,
            'professor_id' => $this->professor->id,
            'nome' => 'Turma da Manhã',
        ]);

        $this->modulo = Modulo::factory()->create(['turma_id' => $this->turma->id, 'nivel' => 'A1']);
    }

    private function matricular(string $nome): User
    {
        $aluno = User::factory()->create(['name' => $nome]);
        $aluno->assignRole('aluno');
        $this->turma->alunos()->attach($aluno->id, ['data_matricula' => now()->subDays(20)->toDateString(), 'status' => 'ativo']);

        return $aluno;
    }

    public function test_mostra_resumo_e_turmas_do_professor(): void
    {
        Conteudo::factory()->count(2)->create(['modulo_id' => $this->modulo->id]);
        $this->matricular('Ana Aluna');
        $this->matricular('Bruno Aluno');

        $this->actingAs($this->professor)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Olá, Paula!')
            ->assertSee('Turma da Manhã')
            ->assertSee('Inglês Geral')
            ->assertSee('Aulas publicadas');
    }

    public function test_so_conta_e_lista_turmas_do_proprio_professor(): void
    {
        $outro = User::factory()->create();
        $outro->assignRole('professor');
        Turma::factory()->create([
            'curso_id' => Curso::factory()->create()->id,
            'professor_id' => $outro->id,
            'nome' => 'Turma do Outro Professor',
        ]);

        $this->actingAs($this->professor)
            ->get(route('dashboard'))
            ->assertSee('Turma da Manhã')
            ->assertDontSee('Turma do Outro Professor');
    }

    public function test_aluno_parado_ha_mais_de_7_dias_aparece_em_atencao_e_ativo_nao(): void
    {
        $conteudo = Conteudo::factory()->create(['modulo_id' => $this->modulo->id]);

        $parado = $this->matricular('Paulo Parado');
        AlunoProgresso::create(['aluno_id' => $parado->id, 'conteudo_id' => $conteudo->id, 'concluido_em' => now()->subDays(12)]);

        $nunca = $this->matricular('Nina Nunca');

        $ativo = $this->matricular('Ana Ativa');
        AlunoProgresso::create(['aluno_id' => $ativo->id, 'conteudo_id' => $conteudo->id, 'concluido_em' => now()->subDay()]);

        $response = $this->actingAs($this->professor)->get(route('dashboard'))->assertOk();
        $response->assertSee('ainda não concluiu nenhuma aula');

        // Quem nunca concluiu nada vem primeiro, depois o parado há mais tempo; o ativo fica de fora.
        Livewire::actingAs($this->professor)
            ->test(Dashboard::class)
            ->assertViewHas('alunosEmAtencao', fn ($lista) => $lista->pluck('aluno.name')->all() === ['Nina Nunca', 'Paulo Parado']);
    }

    public function test_atividade_recente_mostra_conclusoes_dos_alunos_da_turma(): void
    {
        $conteudo = Conteudo::factory()->create(['modulo_id' => $this->modulo->id, 'titulo' => 'Aula de Saudações']);
        $aluna = $this->matricular('Ana Ativa');
        AlunoProgresso::create(['aluno_id' => $aluna->id, 'conteudo_id' => $conteudo->id, 'concluido_em' => now()->subHour()]);

        $this->actingAs($this->professor)
            ->get(route('dashboard'))
            ->assertSee('Ana Ativa')
            ->assertSee('Aula de Saudações');
    }

    public function test_progresso_medio_da_turma_considera_todos_os_alunos(): void
    {
        $conteudos = Conteudo::factory()->count(4)->create(['modulo_id' => $this->modulo->id]);
        $a = $this->matricular('Aluno A');
        $this->matricular('Aluno B');

        // A concluiu 2 de 4 (50%), B 0 de 4 (0%) => média 25%
        foreach ($conteudos->take(2) as $c) {
            AlunoProgresso::create(['aluno_id' => $a->id, 'conteudo_id' => $c->id, 'concluido_em' => now()]);
        }

        $progresso = $this->turma->progressoDosAlunos();

        $this->assertSame(25.0, round($progresso->avg('percentual'), 1));
    }

    public function test_professor_sem_turmas_ve_estado_vazio(): void
    {
        $novo = User::factory()->create();
        $novo->assignRole('professor');

        $this->actingAs($novo)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Você ainda não é responsável por nenhuma turma');
    }
}
