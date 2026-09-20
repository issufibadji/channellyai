<?php

namespace Tests\Feature\Academico;

use App\Livewire\Academico\AulaAoVivoManager;
use App\Livewire\Academico\SalaAoVivo;
use App\Models\AulaAoVivo;
use App\Models\Curso;
use App\Models\Turma;
use App\Models\User;
use App\Notifications\AulaAoVivoAgendada;
use App\Notifications\AulaAoVivoIniciada;
use Database\Seeders\AcademicoPermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class AulaAoVivoTest extends TestCase
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

        $this->professor = User::factory()->create(['name' => 'Paula Professora']);
        $this->professor->assignRole('professor');

        $this->turma = Turma::factory()->create([
            'curso_id' => Curso::factory()->create()->id,
            'professor_id' => $this->professor->id,
            'nome' => 'Turma da Manhã',
        ]);

        $this->aluno = User::factory()->create(['name' => 'Ana Aluna']);
        $this->aluno->assignRole('aluno');
        $this->turma->alunos()->attach($this->aluno->id, ['data_matricula' => now()->toDateString(), 'status' => 'ativo']);
    }

    private function outroProfessor(): User
    {
        $outro = User::factory()->create();
        $outro->assignRole('professor');

        return $outro;
    }

    private function alunoDeFora(): User
    {
        $fora = User::factory()->create();
        $fora->assignRole('aluno');

        return $fora;
    }

    public function test_professor_agenda_aula_e_a_turma_e_notificada(): void
    {
        Notification::fake();

        Livewire::actingAs($this->professor)
            ->test(AulaAoVivoManager::class, ['turma' => $this->turma])
            ->call('create')
            ->set('titulo', 'Revisão de verbos')
            ->set('inicioEm', '2026-10-01T18:30')
            ->set('duracaoMinutos', 90)
            ->call('save')
            ->assertHasNoErrors();

        $aula = AulaAoVivo::where('titulo', 'Revisão de verbos')->first();

        $this->assertNotNull($aula);
        $this->assertSame(AulaAoVivo::AGENDADA, $aula->status);
        $this->assertSame(90, $aula->duracao_minutos);
        $this->assertStringStartsWith('aula-', $aula->sala);
        Notification::assertSentTo($this->aluno, AulaAoVivoAgendada::class);
        Notification::assertNotSentTo($this->professor, AulaAoVivoAgendada::class);
    }

    public function test_valida_titulo_data_e_duracao(): void
    {
        Livewire::actingAs($this->professor)
            ->test(AulaAoVivoManager::class, ['turma' => $this->turma])
            ->set('titulo', '')
            ->set('inicioEm', '')
            ->set('duracaoMinutos', 5)
            ->call('save')
            ->assertHasErrors(['titulo', 'inicioEm', 'duracaoMinutos']);

        $this->assertSame(0, AulaAoVivo::count());
    }

    public function test_outro_professor_e_aluno_nao_acessam_a_gestao_da_turma(): void
    {
        $this->actingAs($this->outroProfessor())
            ->get(route('academico.turmas.ao-vivo', $this->turma))
            ->assertForbidden();

        $this->actingAs($this->aluno)
            ->get(route('academico.turmas.ao-vivo', $this->turma))
            ->assertForbidden();
    }

    public function test_professor_nao_mexe_em_aula_de_outra_turma(): void
    {
        $outraTurma = Turma::factory()->create(['curso_id' => Curso::factory()->create()->id]);
        $alheia = AulaAoVivo::factory()->create(['turma_id' => $outraTurma->id]);

        try {
            Livewire::actingAs($this->professor)
                ->test(AulaAoVivoManager::class, ['turma' => $this->turma])
                ->call('iniciar', $alheia->id);

            $this->fail('Deveria ter falhado com ModelNotFoundException (vira 404 em produção).');
        } catch (ModelNotFoundException) {
            // esperado
        }

        $this->assertSame(AulaAoVivo::AGENDADA, $alheia->fresh()->status);
    }

    public function test_iniciar_abre_a_sala_e_avisa_os_alunos_e_encerrar_fecha(): void
    {
        Notification::fake();
        $aula = AulaAoVivo::factory()->create(['turma_id' => $this->turma->id]);

        $componente = Livewire::actingAs($this->professor)
            ->test(AulaAoVivoManager::class, ['turma' => $this->turma])
            ->call('iniciar', $aula->id);

        $this->assertSame(AulaAoVivo::AO_VIVO, $aula->fresh()->status);
        $this->assertNotNull($aula->fresh()->iniciada_em);
        Notification::assertSentTo($this->aluno, AulaAoVivoIniciada::class);

        $componente->call('encerrar', $aula->id);

        $this->assertSame(AulaAoVivo::ENCERRADA, $aula->fresh()->status);
        $this->assertNotNull($aula->fresh()->encerrada_em);
    }

    public function test_iniciar_duas_vezes_nao_reenvia_notificacao(): void
    {
        Notification::fake();
        $aula = AulaAoVivo::factory()->create(['turma_id' => $this->turma->id]);

        $aula->iniciar();
        $aula->iniciar();

        Notification::assertSentToTimes($this->aluno, AulaAoVivoIniciada::class, 1);
    }

    public function test_aluno_matriculado_ve_a_espera_enquanto_a_aula_nao_comecou_e_sem_expor_a_sala(): void
    {
        $aula = AulaAoVivo::factory()->create(['turma_id' => $this->turma->id, 'sala' => 'aula-segredo123']);

        $this->actingAs($this->aluno)
            ->get(route('academico.ao-vivo.sala', $aula))
            ->assertOk()
            ->assertSee('Aguardando o professor iniciar')
            ->assertDontSee('aula-segredo123')
            ->assertDontSee('<iframe', false);
    }

    public function test_aluno_matriculado_entra_na_sala_quando_esta_ao_vivo_com_camera_e_microfone_desligados(): void
    {
        config(['services.jitsi.domain' => 'jitsi.escola.test']);
        $aula = AulaAoVivo::factory()->aoVivo()->create(['turma_id' => $this->turma->id, 'sala' => 'aula-abc123']);

        $this->actingAs($this->aluno)
            ->get(route('academico.ao-vivo.sala', $aula))
            ->assertOk()
            ->assertSee('<iframe', false)
            ->assertSee('https://jitsi.escola.test/aula-abc123#', false)
            ->assertSee('config.startWithAudioMuted=true', false)
            ->assertSee('config.startWithVideoMuted=true', false)
            ->assertSee('AO VIVO');
    }

    public function test_professor_da_turma_entra_sem_ser_silenciado(): void
    {
        $aula = AulaAoVivo::factory()->aoVivo()->create(['turma_id' => $this->turma->id]);

        $url = $aula->urlSala($this->professor, true);

        $this->assertStringNotContainsString('startWithAudioMuted', $url);
        $this->assertStringContainsString('userInfo.displayName='.rawurlencode('"Paula Professora"'), $url);

        $this->actingAs($this->professor)
            ->get(route('academico.ao-vivo.sala', $aula))
            ->assertOk()
            ->assertSee('<iframe', false)
            ->assertSee('Encerrar aula');
    }

    public function test_quem_nao_pertence_a_turma_nao_entra_na_sala(): void
    {
        $aula = AulaAoVivo::factory()->aoVivo()->create(['turma_id' => $this->turma->id]);

        $this->actingAs($this->alunoDeFora())->get(route('academico.ao-vivo.sala', $aula))->assertForbidden();
        $this->actingAs($this->outroProfessor())->get(route('academico.ao-vivo.sala', $aula))->assertForbidden();
    }

    public function test_aluno_nao_consegue_iniciar_nem_encerrar_pela_sala(): void
    {
        $aula = AulaAoVivo::factory()->create(['turma_id' => $this->turma->id]);

        Livewire::actingAs($this->aluno)
            ->test(SalaAoVivo::class, ['aula' => $aula])
            ->call('iniciar')
            ->assertForbidden();

        $this->assertSame(AulaAoVivo::AGENDADA, $aula->fresh()->status);
    }

    public function test_professor_inicia_pela_propria_sala(): void
    {
        Notification::fake();
        $aula = AulaAoVivo::factory()->create(['turma_id' => $this->turma->id]);

        Livewire::actingAs($this->professor)
            ->test(SalaAoVivo::class, ['aula' => $aula])
            ->assertSee('Iniciar aula agora')
            ->call('iniciar')
            ->assertSee('<iframe', false);

        Notification::assertSentTo($this->aluno, AulaAoVivoIniciada::class);
    }

    public function test_sala_encerrada_mostra_aviso_e_nao_o_iframe(): void
    {
        $aula = AulaAoVivo::factory()->encerrada()->create(['turma_id' => $this->turma->id]);

        $this->actingAs($this->aluno)
            ->get(route('academico.ao-vivo.sala', $aula))
            ->assertOk()
            ->assertSee('já foi encerrada')
            ->assertDontSee('<iframe', false);
    }

    public function test_lista_do_aluno_mostra_so_as_aulas_das_turmas_dele(): void
    {
        AulaAoVivo::factory()->create(['turma_id' => $this->turma->id, 'titulo' => 'Aula da minha turma']);
        $outraTurma = Turma::factory()->create(['curso_id' => Curso::factory()->create()->id]);
        AulaAoVivo::factory()->create(['turma_id' => $outraTurma->id, 'titulo' => 'Aula de outra turma']);

        $this->actingAs($this->aluno)
            ->get(route('academico.ao-vivo.index'))
            ->assertOk()
            ->assertSee('Aula da minha turma')
            ->assertDontSee('Aula de outra turma');
    }

    public function test_dashboard_do_aluno_destaca_aula_ao_vivo(): void
    {
        AulaAoVivo::factory()->aoVivo()->create(['turma_id' => $this->turma->id, 'titulo' => 'Conversação ao vivo']);

        $this->actingAs($this->aluno)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Conversação ao vivo')
            ->assertSee('AO VIVO');
    }
}
