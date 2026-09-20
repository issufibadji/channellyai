<?php

namespace Tests\Feature\Academico;

use App\Livewire\Academico\AulaAoVivoManager;
use App\Models\AulaAoVivo;
use App\Models\Conteudo;
use App\Models\Curso;
use App\Models\Modulo;
use App\Models\Turma;
use App\Models\User;
use App\Notifications\AulaAoVivoGravada;
use Database\Seeders\AcademicoPermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Tests\TestCase;

class GravacaoAulaAoVivoTest extends TestCase
{
    use RefreshDatabase;

    private const LINK = 'https://drive.google.com/file/d/1dwCjrZftEnNUjx-Kz8b8vegfteSUPS6g/view?usp=sharing';

    private User $professor;

    private User $aluno;

    private Turma $turma;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->seed(AcademicoPermissionSeeder::class);

        $this->professor = User::factory()->create();
        $this->professor->assignRole('professor');

        $this->turma = Turma::factory()->create([
            'curso_id' => Curso::factory()->create()->id,
            'professor_id' => $this->professor->id,
        ]);

        $this->aluno = User::factory()->create();
        $this->aluno->assignRole('aluno');
        $this->turma->alunos()->attach($this->aluno->id, ['data_matricula' => now()->subDay()->toDateString(), 'status' => 'ativo']);
    }

    private function manager(): Testable
    {
        return Livewire::actingAs($this->professor)->test(AulaAoVivoManager::class, ['turma' => $this->turma]);
    }

    public function test_publicar_cria_video_no_modulo_aulas_gravadas_de_extras_e_avisa_os_alunos(): void
    {
        Notification::fake();
        $aula = AulaAoVivo::factory()->encerrada()->create(['turma_id' => $this->turma->id, 'titulo' => 'Revisão de verbos']);

        $this->manager()
            ->call('abrirGravacao', $aula->id)
            ->set('gravacaoUrl', self::LINK)
            ->call('salvarGravacao')
            ->assertHasNoErrors();

        $conteudo = $aula->fresh()->conteudo;

        $this->assertNotNull($conteudo);
        $this->assertSame('video', $conteudo->tipo);
        $this->assertSame('Revisão de verbos', $conteudo->titulo);
        $this->assertSame(self::LINK, $conteudo->url_externa);
        $this->assertSame('Aulas Gravadas', $conteudo->modulo->nome);
        $this->assertSame('extra', $conteudo->modulo->categoria);
        $this->assertSame('Aulas Gravadas', $conteudo->modulo->secao);
        Notification::assertSentTo($this->aluno, AulaAoVivoGravada::class);
    }

    public function test_publicar_num_modulo_existente_da_turma(): void
    {
        $modulo = Modulo::factory()->create(['turma_id' => $this->turma->id, 'nome' => 'Módulo A1']);
        $aula = AulaAoVivo::factory()->encerrada()->create(['turma_id' => $this->turma->id]);

        $this->manager()
            ->call('abrirGravacao', $aula->id)
            ->set('gravacaoUrl', self::LINK)
            ->set('moduloDestino', (string) $modulo->id)
            ->call('salvarGravacao')
            ->assertHasNoErrors();

        $this->assertSame($modulo->id, $aula->fresh()->conteudo->modulo_id);
        $this->assertSame(0, Modulo::where('nome', 'Aulas Gravadas')->count());
    }

    public function test_republicar_atualiza_o_mesmo_video_sem_duplicar_nem_renotificar(): void
    {
        Notification::fake();
        $aula = AulaAoVivo::factory()->encerrada()->create(['turma_id' => $this->turma->id]);

        $componente = $this->manager()
            ->call('abrirGravacao', $aula->id)
            ->set('gravacaoUrl', self::LINK)
            ->call('salvarGravacao');

        $componente
            ->call('abrirGravacao', $aula->id)
            ->set('gravacaoUrl', 'https://youtu.be/dQw4w9WgXcQ')
            ->call('salvarGravacao')
            ->assertHasNoErrors();

        $this->assertSame(1, Conteudo::count());
        $this->assertSame('https://youtu.be/dQw4w9WgXcQ', $aula->fresh()->conteudo->url_externa);
        Notification::assertSentToTimes($this->aluno, AulaAoVivoGravada::class, 1);
    }

    public function test_so_publica_gravacao_de_aula_encerrada(): void
    {
        $aula = AulaAoVivo::factory()->aoVivo()->create(['turma_id' => $this->turma->id]);

        $this->manager()
            ->call('abrirGravacao', $aula->id)
            ->set('gravacaoUrl', self::LINK)
            ->call('salvarGravacao')
            ->assertHasErrors('gravacaoUrl');

        $this->assertSame(0, Conteudo::count());
    }

    public function test_exige_link_valido(): void
    {
        $aula = AulaAoVivo::factory()->encerrada()->create(['turma_id' => $this->turma->id]);

        $this->manager()->call('abrirGravacao', $aula->id)->set('gravacaoUrl', '')->call('salvarGravacao')->assertHasErrors('gravacaoUrl');
        $this->manager()->call('abrirGravacao', $aula->id)->set('gravacaoUrl', 'nao e url')->call('salvarGravacao')->assertHasErrors('gravacaoUrl');

        $this->assertSame(0, Conteudo::count());
    }

    public function test_nao_publica_em_modulo_de_outra_turma(): void
    {
        $outraTurma = Turma::factory()->create(['curso_id' => Curso::factory()->create()->id]);
        $alheio = Modulo::factory()->create(['turma_id' => $outraTurma->id]);
        $aula = AulaAoVivo::factory()->encerrada()->create(['turma_id' => $this->turma->id]);

        try {
            $this->manager()
                ->call('abrirGravacao', $aula->id)
                ->set('gravacaoUrl', self::LINK)
                ->set('moduloDestino', (string) $alheio->id)
                ->call('salvarGravacao');

            $this->fail('Deveria ter falhado com ModelNotFoundException (vira 404 em produção).');
        } catch (ModelNotFoundException) {
            // esperado
        }

        $this->assertSame(0, Conteudo::count());
    }

    public function test_aluno_assiste_a_gravacao_pela_lista_e_o_player_usa_o_embed_do_drive(): void
    {
        $aula = AulaAoVivo::factory()->encerrada()->create(['turma_id' => $this->turma->id, 'titulo' => 'Aula gravada']);
        $aula->publicarGravacao(self::LINK);

        $resposta = $this->actingAs($this->aluno)->get(route('academico.ao-vivo.index'));

        $resposta->assertOk()
            ->assertSee('Assistir gravação')
            ->assertSee(route('academico.minha-turma.aula', [$this->turma, $aula->fresh()->conteudo]), false);

        $this->actingAs($this->aluno)
            ->get(route('academico.minha-turma.aula', [$this->turma, $aula->fresh()->conteudo]))
            ->assertOk()
            ->assertSee('https://drive.google.com/file/d/1dwCjrZftEnNUjx-Kz8b8vegfteSUPS6g/preview', false);
    }

    public function test_gravacao_em_aulas_gravadas_nao_entra_no_progresso_do_curso(): void
    {
        $aula = AulaAoVivo::factory()->encerrada()->create(['turma_id' => $this->turma->id]);
        $aula->publicarGravacao(self::LINK);

        $this->assertSame(0, $this->turma->totalAulas());
    }

    public function test_apagar_o_video_solta_o_vinculo_sem_apagar_a_aula(): void
    {
        $aula = AulaAoVivo::factory()->encerrada()->create(['turma_id' => $this->turma->id]);
        $aula->publicarGravacao(self::LINK);

        $aula->fresh()->conteudo->delete();

        $this->assertNull($aula->fresh()->conteudo_id);
        $this->assertNotNull(AulaAoVivo::find($aula->id));
    }
}
