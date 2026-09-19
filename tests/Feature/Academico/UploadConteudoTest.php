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
use Livewire\Livewire;
use Tests\TestCase;

class UploadConteudoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->seed(AcademicoPermissionSeeder::class);
    }

    private function turmaEModuloDoProfessor(): array
    {
        $curso = Curso::factory()->create();
        $professor = User::factory()->create();
        $professor->assignRole('professor');
        $turma = Turma::factory()->create(['curso_id' => $curso->id, 'professor_id' => $professor->id]);
        $modulo = Modulo::factory()->create(['turma_id' => $turma->id]);

        return [$professor, $turma, $modulo];
    }

    public function test_upload_de_pdf_valido_salva_e_grava_arquivo_path(): void
    {
        Storage::fake('public');

        [$professor, $turma, $modulo] = $this->turmaEModuloDoProfessor();

        Livewire::actingAs($professor)
            ->test(ModuloConteudoManager::class, ['turma' => $turma])
            ->call('createConteudo', $modulo->id)
            ->set('titulo', 'Apostila')
            ->set('tipo', 'pdf')
            ->set('arquivo', UploadedFile::fake()->create('apostila.pdf', 500, 'application/pdf'))
            ->call('saveConteudo')
            ->assertHasNoErrors();

        $conteudo = Conteudo::where('titulo', 'Apostila')->first();

        $this->assertNotNull($conteudo);
        $this->assertNotNull($conteudo->arquivo_path);
        Storage::disk('public')->assertExists($conteudo->arquivo_path);
    }

    public function test_upload_acima_do_limite_e_rejeitado(): void
    {
        Storage::fake('public');

        [$professor, $turma, $modulo] = $this->turmaEModuloDoProfessor();

        Livewire::actingAs($professor)
            ->test(ModuloConteudoManager::class, ['turma' => $turma])
            ->call('createConteudo', $modulo->id)
            ->set('titulo', 'Apostila Grande')
            ->set('tipo', 'pdf')
            ->set('arquivo', UploadedFile::fake()->create('grande.pdf', 20481, 'application/pdf'))
            ->call('saveConteudo')
            ->assertHasErrors('arquivo');

        $this->assertNull(Conteudo::where('titulo', 'Apostila Grande')->first());
    }

    public function test_upload_de_mime_nao_permitido_e_rejeitado(): void
    {
        Storage::fake('public');

        [$professor, $turma, $modulo] = $this->turmaEModuloDoProfessor();

        Livewire::actingAs($professor)
            ->test(ModuloConteudoManager::class, ['turma' => $turma])
            ->call('createConteudo', $modulo->id)
            ->set('titulo', 'Apostila Errada')
            ->set('tipo', 'pdf')
            ->set('arquivo', UploadedFile::fake()->create('apostila.exe', 100, 'application/x-msdownload'))
            ->call('saveConteudo')
            ->assertHasErrors('arquivo');

        $this->assertNull(Conteudo::where('titulo', 'Apostila Errada')->first());
    }

    public function test_upload_de_exercicio_anexo_aceita_doc_e_docx(): void
    {
        Storage::fake('public');

        [$professor, $turma, $modulo] = $this->turmaEModuloDoProfessor();

        Livewire::actingAs($professor)
            ->test(ModuloConteudoManager::class, ['turma' => $turma])
            ->call('createConteudo', $modulo->id)
            ->set('titulo', 'Exercício Anexo')
            ->set('tipo', 'exercicio')
            ->set('exercicioSubtipo', 'anexo')
            ->set('arquivo', UploadedFile::fake()->create('exercicio.docx', 300, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'))
            ->call('saveConteudo')
            ->assertHasNoErrors();

        $conteudo = Conteudo::where('titulo', 'Exercício Anexo')->first();

        $this->assertNotNull($conteudo);
        $this->assertNotNull($conteudo->arquivo_path);
        Storage::disk('public')->assertExists($conteudo->arquivo_path);
    }

    public function test_embed_url_video_reconhece_youtube_e_vimeo(): void
    {
        $youtube = new Conteudo(['url_externa' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ']);
        $this->assertSame('https://www.youtube.com/embed/dQw4w9WgXcQ', $youtube->embedUrlVideo());

        $youtubeCurto = new Conteudo(['url_externa' => 'https://youtu.be/dQw4w9WgXcQ']);
        $this->assertSame('https://www.youtube.com/embed/dQw4w9WgXcQ', $youtubeCurto->embedUrlVideo());

        $vimeo = new Conteudo(['url_externa' => 'https://vimeo.com/76979871']);
        $this->assertSame('https://player.vimeo.com/video/76979871', $vimeo->embedUrlVideo());

        $desconhecida = new Conteudo(['url_externa' => 'https://example.com/video']);
        $this->assertNull($desconhecida->embedUrlVideo());

        $semUrl = new Conteudo(['url_externa' => null]);
        $this->assertNull($semUrl->embedUrlVideo());
    }
}
