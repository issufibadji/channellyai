<?php

namespace Tests\Feature\Academico;

use App\Livewire\Academico\Aluno\Aula;
use App\Models\AlunoRespostaOpcao;
use App\Models\Conteudo;
use App\Models\Curso;
use App\Models\ExercicioOpcao;
use App\Models\ExercicioPergunta;
use App\Models\Modulo;
use App\Models\Turma;
use App\Models\User;
use Database\Seeders\AcademicoPermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class QuizTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->seed(AcademicoPermissionSeeder::class);
    }

    private function turmaComAlunoMatriculado(): array
    {
        $curso = Curso::factory()->create();
        $professor = User::factory()->create();
        $professor->assignRole('professor');
        $turma = Turma::factory()->create(['curso_id' => $curso->id, 'professor_id' => $professor->id]);

        $aluno = User::factory()->create();
        $aluno->assignRole('aluno');
        $turma->alunos()->attach($aluno->id, ['data_matricula' => now()->subDays(10)->toDateString(), 'status' => 'ativo']);

        $modulo = Modulo::factory()->create(['turma_id' => $turma->id, 'categoria' => 'nivel', 'nivel' => 'A1']);

        return [$turma, $modulo, $aluno];
    }

    private function conteudoQuiz(Modulo $modulo): Conteudo
    {
        return Conteudo::factory()->create([
            'modulo_id' => $modulo->id,
            'tipo' => 'exercicio',
            'exercicio_subtipo' => 'quiz',
            'dias_liberacao' => 0,
            'bloqueado' => false,
        ]);
    }

    public function test_corrigir_respostas_com_pergunta_de_resposta_unica(): void
    {
        [, $modulo, $aluno] = $this->turmaComAlunoMatriculado();
        $conteudo = $this->conteudoQuiz($modulo);

        $pergunta = ExercicioPergunta::factory()->create(['conteudo_id' => $conteudo->id]);
        $certa = ExercicioOpcao::factory()->correta()->create(['pergunta_id' => $pergunta->id]);
        $errada = ExercicioOpcao::factory()->incorreta()->create(['pergunta_id' => $pergunta->id]);

        AlunoRespostaOpcao::create(['aluno_id' => $aluno->id, 'opcao_id' => $certa->id]);
        $this->assertSame(100.0, $conteudo->corrigirRespostas($aluno));

        AlunoRespostaOpcao::where('aluno_id', $aluno->id)->delete();
        AlunoRespostaOpcao::create(['aluno_id' => $aluno->id, 'opcao_id' => $errada->id]);
        $this->assertSame(0.0, $conteudo->corrigirRespostas($aluno));
    }

    public function test_corrigir_respostas_com_pergunta_de_multiplas_corretas(): void
    {
        [, $modulo, $aluno] = $this->turmaComAlunoMatriculado();
        $conteudo = $this->conteudoQuiz($modulo);

        $pergunta = ExercicioPergunta::factory()->create(['conteudo_id' => $conteudo->id]);
        $certa1 = ExercicioOpcao::factory()->correta()->create(['pergunta_id' => $pergunta->id]);
        $certa2 = ExercicioOpcao::factory()->correta()->create(['pergunta_id' => $pergunta->id]);
        $errada = ExercicioOpcao::factory()->incorreta()->create(['pergunta_id' => $pergunta->id]);

        // Marcou exatamente o conjunto certo.
        AlunoRespostaOpcao::insert([
            ['aluno_id' => $aluno->id, 'opcao_id' => $certa1->id, 'created_at' => now(), 'updated_at' => now()],
            ['aluno_id' => $aluno->id, 'opcao_id' => $certa2->id, 'created_at' => now(), 'updated_at' => now()],
        ]);
        $this->assertSame(100.0, $conteudo->corrigirRespostas($aluno));

        // Marcou a menos (só uma das duas corretas).
        AlunoRespostaOpcao::where('aluno_id', $aluno->id)->delete();
        AlunoRespostaOpcao::create(['aluno_id' => $aluno->id, 'opcao_id' => $certa1->id]);
        $this->assertSame(0.0, $conteudo->corrigirRespostas($aluno));

        // Marcou a mais (as duas certas + a errada).
        AlunoRespostaOpcao::where('aluno_id', $aluno->id)->delete();
        AlunoRespostaOpcao::insert([
            ['aluno_id' => $aluno->id, 'opcao_id' => $certa1->id, 'created_at' => now(), 'updated_at' => now()],
            ['aluno_id' => $aluno->id, 'opcao_id' => $certa2->id, 'created_at' => now(), 'updated_at' => now()],
            ['aluno_id' => $aluno->id, 'opcao_id' => $errada->id, 'created_at' => now(), 'updated_at' => now()],
        ]);
        $this->assertSame(0.0, $conteudo->corrigirRespostas($aluno));
    }

    public function test_corrigir_respostas_sem_perguntas_retorna_zero(): void
    {
        [, $modulo, $aluno] = $this->turmaComAlunoMatriculado();
        $conteudo = $this->conteudoQuiz($modulo);

        $this->assertSame(0.0, $conteudo->corrigirRespostas($aluno));
    }

    public function test_enviar_quiz_marca_aluno_progresso_automaticamente(): void
    {
        [$turma, $modulo, $aluno] = $this->turmaComAlunoMatriculado();
        $conteudo = $this->conteudoQuiz($modulo);

        $pergunta = ExercicioPergunta::factory()->create(['conteudo_id' => $conteudo->id]);
        $certa = ExercicioOpcao::factory()->correta()->create(['pergunta_id' => $pergunta->id]);

        Livewire::actingAs($aluno)
            ->test(Aula::class, ['turma' => $turma, 'conteudo' => $conteudo])
            ->set("respostasSelecionadas.{$pergunta->id}", [$certa->id])
            ->call('enviarQuiz');

        $this->assertDatabaseHas('aluno_progresso', ['aluno_id' => $aluno->id, 'conteudo_id' => $conteudo->id]);
    }

    public function test_reenvio_de_quiz_sobrescreve_respostas_anteriores(): void
    {
        [$turma, $modulo, $aluno] = $this->turmaComAlunoMatriculado();
        $conteudo = $this->conteudoQuiz($modulo);

        $pergunta = ExercicioPergunta::factory()->create(['conteudo_id' => $conteudo->id]);
        $opcaoA = ExercicioOpcao::factory()->incorreta()->create(['pergunta_id' => $pergunta->id]);
        $opcaoB = ExercicioOpcao::factory()->correta()->create(['pergunta_id' => $pergunta->id]);

        $component = Livewire::actingAs($aluno)->test(Aula::class, ['turma' => $turma, 'conteudo' => $conteudo]);

        $component->set("respostasSelecionadas.{$pergunta->id}", [$opcaoA->id])->call('enviarQuiz');
        $this->assertSame(1, AlunoRespostaOpcao::where('aluno_id', $aluno->id)->count());
        $this->assertSame(0.0, $conteudo->corrigirRespostas($aluno));

        $component->set("respostasSelecionadas.{$pergunta->id}", [$opcaoB->id])->call('enviarQuiz');
        $this->assertSame(1, AlunoRespostaOpcao::where('aluno_id', $aluno->id)->count());
        $this->assertSame(100.0, $conteudo->corrigirRespostas($aluno));
    }

    public function test_403_ao_enviar_quiz_de_conteudo_bloqueado(): void
    {
        [$turma, $modulo, $aluno] = $this->turmaComAlunoMatriculado();
        $conteudo = Conteudo::factory()->create([
            'modulo_id' => $modulo->id,
            'tipo' => 'exercicio',
            'exercicio_subtipo' => 'quiz',
            'bloqueado' => true,
        ]);

        Livewire::actingAs($aluno)
            ->test(Aula::class, ['turma' => $turma, 'conteudo' => $conteudo])
            ->call('enviarQuiz')
            ->assertForbidden();
    }
}
