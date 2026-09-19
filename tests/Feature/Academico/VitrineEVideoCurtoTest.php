<?php

namespace Tests\Feature\Academico;

use App\Livewire\Academico\ModuloConteudoManager;
use App\Models\Conteudo;
use App\Models\Curso;
use App\Models\Modulo;
use App\Models\Turma;
use App\Models\User;
use Database\Seeders\AcademicoPermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Tests\TestCase;

class VitrineEVideoCurtoTest extends TestCase
{
    use RefreshDatabase;

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
        $this->turma->alunos()->attach($this->aluno->id, ['data_matricula' => now()->subDays(10)->toDateString(), 'status' => 'ativo']);
    }

    private function manager(): Testable
    {
        return Livewire::actingAs($this->professor)->test(ModuloConteudoManager::class, ['turma' => $this->turma]);
    }

    public function test_upload_de_video_curto_valido_salva_arquivo_path(): void
    {
        Storage::fake('public');
        $modulo = Modulo::factory()->create(['turma_id' => $this->turma->id]);

        $this->manager()
            ->call('createConteudo', $modulo->id)
            ->set('titulo', 'Frase do dia')
            ->set('tipo', 'video_curto')
            ->set('arquivo', UploadedFile::fake()->create('frase.mp4', 5000, 'video/mp4'))
            ->call('saveConteudo')
            ->assertHasNoErrors();

        $conteudo = Conteudo::where('titulo', 'Frase do dia')->first();

        $this->assertSame('video_curto', $conteudo->tipo);
        Storage::disk('public')->assertExists($conteudo->arquivo_path);
    }

    public function test_video_curto_rejeita_formato_e_tamanho_invalidos(): void
    {
        Storage::fake('public');
        $modulo = Modulo::factory()->create(['turma_id' => $this->turma->id]);

        $this->manager()
            ->call('createConteudo', $modulo->id)
            ->set('titulo', 'Errado')
            ->set('tipo', 'video_curto')
            ->set('arquivo', UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf'))
            ->call('saveConteudo')
            ->assertHasErrors('arquivo');

        $this->manager()
            ->call('createConteudo', $modulo->id)
            ->set('titulo', 'Grande')
            ->set('tipo', 'video_curto')
            ->set('arquivo', UploadedFile::fake()->create('grande.mp4', 51201, 'video/mp4'))
            ->call('saveConteudo')
            ->assertHasErrors('arquivo');

        $this->assertSame(0, Conteudo::count());
    }

    public function test_video_curto_exige_arquivo_na_criacao(): void
    {
        $modulo = Modulo::factory()->create(['turma_id' => $this->turma->id]);

        $this->manager()
            ->call('createConteudo', $modulo->id)
            ->set('titulo', 'Sem arquivo')
            ->set('tipo', 'video_curto')
            ->call('saveConteudo')
            ->assertHasErrors('arquivo');
    }

    public function test_professor_salva_secao_e_capa_do_modulo(): void
    {
        Storage::fake('public');

        $this->manager()
            ->call('createModulo')
            ->set('moduloNome', 'Nível A1')
            ->set('nivel', 'A1')
            ->set('secao', 'Vídeos Curtos')
            ->set('capa', UploadedFile::fake()->create('capa.jpg', 200, 'image/jpeg'))
            ->call('saveModulo')
            ->assertHasNoErrors();

        $modulo = Modulo::where('nome', 'Nível A1')->first();

        $this->assertSame('Vídeos Curtos', $modulo->secao);
        Storage::disk('public')->assertExists($modulo->capa_path);
    }

    public function test_capa_rejeita_arquivo_que_nao_e_imagem(): void
    {
        Storage::fake('public');

        $this->manager()
            ->call('createModulo')
            ->set('moduloNome', 'Nível A1')
            ->set('capa', UploadedFile::fake()->create('capa.pdf', 100, 'application/pdf'))
            ->call('saveModulo')
            ->assertHasErrors('capa');
    }

    public function test_limite_de_upload_do_livewire_comporta_pdf_e_video(): void
    {
        // Sem essa config o Livewire barra em 12MB, antes da validação do componente.
        $this->assertContains('max:51200', config('livewire.temporary_file_upload.rules'));
    }

    public function test_pagina_de_aula_renderiza_o_visualizador_de_pdf(): void
    {
        $modulo = Modulo::factory()->create(['turma_id' => $this->turma->id]);
        $conteudo = Conteudo::factory()->create([
            'modulo_id' => $modulo->id,
            'tipo' => 'pdf',
            'arquivo_path' => 'conteudos/mapa-mental.pdf',
            'dias_liberacao' => 0,
        ]);

        $this->actingAs($this->aluno)
            ->get(route('academico.minha-turma.aula', [$this->turma, $conteudo]))
            ->assertOk()
            ->assertSee('<iframe', false)
            ->assertSee('storage/conteudos/mapa-mental.pdf', false);
    }

    public function test_exercicio_anexo_em_pdf_tambem_usa_o_visualizador(): void
    {
        $modulo = Modulo::factory()->create(['turma_id' => $this->turma->id]);
        $conteudo = Conteudo::factory()->create([
            'modulo_id' => $modulo->id,
            'tipo' => 'exercicio',
            'exercicio_subtipo' => 'anexo',
            'arquivo_path' => 'conteudos/exercicio.pdf',
            'dias_liberacao' => 0,
        ]);

        $this->actingAs($this->aluno)
            ->get(route('academico.minha-turma.aula', [$this->turma, $conteudo]))
            ->assertOk()
            ->assertSee('<iframe', false);
    }

    public function test_exercicio_anexo_em_docx_fica_so_como_link(): void
    {
        $modulo = Modulo::factory()->create(['turma_id' => $this->turma->id]);
        $conteudo = Conteudo::factory()->create([
            'modulo_id' => $modulo->id,
            'tipo' => 'exercicio',
            'exercicio_subtipo' => 'anexo',
            'arquivo_path' => 'conteudos/exercicio.docx',
            'dias_liberacao' => 0,
        ]);

        $this->actingAs($this->aluno)
            ->get(route('academico.minha-turma.aula', [$this->turma, $conteudo]))
            ->assertOk()
            ->assertDontSee('<iframe', false)
            ->assertSee('Abrir / baixar arquivo');
    }

    public function test_pagina_de_aula_renderiza_o_player_do_video_curto(): void
    {
        $modulo = Modulo::factory()->create(['turma_id' => $this->turma->id]);
        $conteudo = Conteudo::factory()->create([
            'modulo_id' => $modulo->id,
            'tipo' => 'video_curto',
            'arquivo_path' => 'conteudos/frase.mp4',
            'dias_liberacao' => 0,
        ]);

        $this->actingAs($this->aluno)
            ->get(route('academico.minha-turma.aula', [$this->turma, $conteudo]))
            ->assertOk()
            ->assertSee('<video', false)
            ->assertSee('storage/conteudos/frase.mp4', false);
    }

    public function test_pagina_de_aula_bloqueada_nao_expoe_o_conteudo(): void
    {
        $modulo = Modulo::factory()->create(['turma_id' => $this->turma->id]);
        $conteudo = Conteudo::factory()->create([
            'modulo_id' => $modulo->id,
            'tipo' => 'video_curto',
            'arquivo_path' => 'conteudos/secreto.mp4',
            'dias_liberacao' => 30,
        ]);

        $this->actingAs($this->aluno)
            ->get(route('academico.minha-turma.aula', [$this->turma, $conteudo]))
            ->assertOk()
            ->assertSee('libera em 20 dia')
            ->assertDontSee('secreto.mp4');
    }

    public function test_aula_de_outra_turma_da_404_e_aluno_nao_matriculado_da_403(): void
    {
        $outraTurma = Turma::factory()->create(['curso_id' => Curso::factory()->create()->id]);
        $modulo = Modulo::factory()->create(['turma_id' => $outraTurma->id]);
        $conteudo = Conteudo::factory()->create(['modulo_id' => $modulo->id]);

        // conteúdo existe, mas não pertence à turma da URL
        $this->actingAs($this->aluno)
            ->get(route('academico.minha-turma.aula', [$this->turma, $conteudo]))
            ->assertNotFound();

        // conteúdo e turma coerentes, mas o aluno não está matriculado nela
        $this->actingAs($this->aluno)
            ->get(route('academico.minha-turma.aula', [$outraTurma, $conteudo]))
            ->assertForbidden();
    }

    public function test_navegacao_da_aula_fica_dentro_da_mesma_secao(): void
    {
        $mapas = Modulo::factory()->create(['turma_id' => $this->turma->id, 'nivel' => 'A1', 'secao' => 'Mapas Mentais']);
        $videos = Modulo::factory()->create(['turma_id' => $this->turma->id, 'nivel' => 'A1', 'secao' => 'Vídeos Curtos']);

        $mapa = Conteudo::factory()->create(['modulo_id' => $mapas->id, 'ordem' => 1]);
        Conteudo::factory()->create(['modulo_id' => $videos->id, 'ordem' => 1]);

        $vizinhos = $this->turma->vizinhosDoConteudo($mapa);

        $this->assertNull($vizinhos['anterior']);
        $this->assertNull($vizinhos['proximo']);
    }

    public function test_vitrine_marca_modulo_travado_com_dias_restantes(): void
    {
        $liberado = Modulo::factory()->create(['turma_id' => $this->turma->id, 'nivel' => 'A1']);
        Conteudo::factory()->create(['modulo_id' => $liberado->id, 'dias_liberacao' => 0]);

        $travado = Modulo::factory()->create(['turma_id' => $this->turma->id, 'nivel' => 'B1']);
        Conteudo::factory()->create(['modulo_id' => $travado->id, 'dias_liberacao' => 13]);

        $manual = Modulo::factory()->create(['turma_id' => $this->turma->id, 'nivel' => 'C1']);
        Conteudo::factory()->bloqueado()->create(['modulo_id' => $manual->id]);

        $cards = collect($this->turma->vitrinePara($this->aluno)[0]['cards'])->keyBy(fn ($c) => $c['modulo']->nivel);

        $this->assertNull($cards['A1']['bloqueio']);
        $this->assertSame(3, $cards['B1']['bloqueio']);
        $this->assertSame('bloqueado', $cards['C1']['bloqueio']);
    }

    public function test_dashboard_do_aluno_mostra_carrossel_com_capa_e_secoes(): void
    {
        $modulo = Modulo::factory()->create([
            'turma_id' => $this->turma->id,
            'nivel' => 'A1',
            'nome' => 'Mapa A1',
            'secao' => 'Mapas Mentais',
            'capa_path' => 'modulos-capas/a1.jpg',
        ]);
        Conteudo::factory()->create(['modulo_id' => $modulo->id, 'dias_liberacao' => 0]);
        Modulo::factory()->extra()->create(['turma_id' => $this->turma->id, 'nome' => 'Clube de Conversação']);

        $this->actingAs($this->aluno)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Área do Aluno')
            ->assertSee('Mapas Mentais')
            ->assertSee('storage/modulos-capas/a1.jpg', false)
            ->assertSee('Seus bônus do curso')
            ->assertSee('Continuar assistindo');
    }
}
