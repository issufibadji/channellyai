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

    public function test_video_curto_salva_o_link_do_google_drive(): void
    {
        $modulo = Modulo::factory()->create(['turma_id' => $this->turma->id]);

        $this->manager()
            ->call('createConteudo', $modulo->id)
            ->set('titulo', 'Frase do dia')
            ->set('tipo', 'video_curto')
            ->set('urlExterna', 'https://drive.google.com/file/d/1dwCjrZftEnNUjx-Kz8b8vegfteSUPS6g/view?usp=sharing')
            ->call('saveConteudo')
            ->assertHasNoErrors();

        $conteudo = Conteudo::where('titulo', 'Frase do dia')->first();

        $this->assertSame('video_curto', $conteudo->tipo);
        $this->assertSame('https://drive.google.com/file/d/1dwCjrZftEnNUjx-Kz8b8vegfteSUPS6g/view?usp=sharing', $conteudo->url_externa);
        $this->assertNull($conteudo->arquivo_path);
    }

    public function test_video_curto_exige_um_link_valido(): void
    {
        $modulo = Modulo::factory()->create(['turma_id' => $this->turma->id]);

        $this->manager()
            ->call('createConteudo', $modulo->id)
            ->set('titulo', 'Sem link')
            ->set('tipo', 'video_curto')
            ->call('saveConteudo')
            ->assertHasErrors('urlExterna');

        $this->manager()
            ->call('createConteudo', $modulo->id)
            ->set('titulo', 'Link ruim')
            ->set('tipo', 'video_curto')
            ->set('urlExterna', 'isso nao e uma url')
            ->call('saveConteudo')
            ->assertHasErrors('urlExterna');

        $this->assertSame(0, Conteudo::count());
    }

    public function test_editar_video_curto_antigo_com_arquivo_limpa_o_arquivo_orfao(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('conteudos/antigo.mp4', 'lixo');

        $modulo = Modulo::factory()->create(['turma_id' => $this->turma->id]);
        $conteudo = Conteudo::factory()->create([
            'modulo_id' => $modulo->id,
            'tipo' => 'video_curto',
            'arquivo_path' => 'conteudos/antigo.mp4',
        ]);

        $this->manager()
            ->call('editConteudo', $conteudo->id)
            ->set('urlExterna', 'https://drive.google.com/file/d/ABC123_-x/view')
            ->call('saveConteudo')
            ->assertHasNoErrors();

        $this->assertNull($conteudo->fresh()->arquivo_path);
        Storage::disk('public')->assertMissing('conteudos/antigo.mp4');
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

    public function test_limite_de_upload_do_livewire_comporta_pdf_de_20mb(): void
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

    public function test_pagina_de_aula_renderiza_o_player_embutido_do_video_curto(): void
    {
        $modulo = Modulo::factory()->create(['turma_id' => $this->turma->id]);
        $conteudo = Conteudo::factory()->create([
            'modulo_id' => $modulo->id,
            'tipo' => 'video_curto',
            'url_externa' => 'https://drive.google.com/file/d/1dwCjrZftEnNUjx-Kz8b8vegfteSUPS6g/view?t=10.723',
            'dias_liberacao' => 0,
        ]);

        $this->actingAs($this->aluno)
            ->get(route('academico.minha-turma.aula', [$this->turma, $conteudo]))
            ->assertOk()
            ->assertSee('<iframe', false)
            ->assertSee('https://drive.google.com/file/d/1dwCjrZftEnNUjx-Kz8b8vegfteSUPS6g/preview', false);
    }

    public function test_pagina_de_aula_bloqueada_nao_expoe_o_conteudo(): void
    {
        $modulo = Modulo::factory()->create(['turma_id' => $this->turma->id]);
        $conteudo = Conteudo::factory()->create([
            'modulo_id' => $modulo->id,
            'tipo' => 'video_curto',
            'url_externa' => 'https://drive.google.com/file/d/SEGREDO123/view',
            'dias_liberacao' => 30,
        ]);

        $this->actingAs($this->aluno)
            ->get(route('academico.minha-turma.aula', [$this->turma, $conteudo]))
            ->assertOk()
            ->assertSee('libera em 20 dia')
            ->assertDontSee('SEGREDO123');
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
