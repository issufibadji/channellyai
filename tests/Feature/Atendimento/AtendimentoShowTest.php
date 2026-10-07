<?php

namespace Tests\Feature\Atendimento;

use App\Livewire\Atendimento\AtendimentoShow;
use App\Models\Atendimento\Atendimento;
use App\Models\Atendimento\Canal;
use App\Models\Atendimento\Cliente;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\WithEstabelecimentoAtual;
use Tests\TestCase;

class AtendimentoShowTest extends TestCase
{
    use RefreshDatabase, WithEstabelecimentoAtual;

    protected Atendimento $atendimento;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->definirEstabelecimentoAtual($this->criarEstabelecimento());

        $cliente = Cliente::create(['nome' => 'Maria']);
        $canal = Canal::create(['nome' => 'WhatsApp', 'tipo' => 'whatsapp']);
        $this->atendimento = Atendimento::create([
            'cliente_id' => $cliente->id,
            'canal_id' => $canal->id,
            'status' => 'aberto',
        ]);
    }

    private function operator(): User
    {
        $user = User::factory()->create();
        $user->assignRole('operator');

        return $user;
    }

    public function test_atendimento_criado_sem_origem_explicita_recebe_origem_manual(): void
    {
        $this->assertSame('manual', $this->atendimento->fresh()->origem);
    }

    public function test_client_message_is_recorded(): void
    {
        Livewire::actingAs($this->operator())
            ->test(AtendimentoShow::class, ['atendimento' => $this->atendimento])
            ->set('remetente', 'cliente')
            ->set('mensagem', 'Preciso de ajuda')
            ->call('enviarMensagem');

        $this->assertDatabaseHas('atendimento_mensagens', ['remetente' => 'cliente', 'conteudo' => 'Preciso de ajuda']);
    }

    public function test_attendant_reply_assigns_and_moves_to_em_atendimento(): void
    {
        $operator = $this->operator();

        Livewire::actingAs($operator)
            ->test(AtendimentoShow::class, ['atendimento' => $this->atendimento])
            ->set('remetente', 'atendente')
            ->set('mensagem', 'Posso ajudar?')
            ->call('enviarMensagem');

        $fresh = $this->atendimento->fresh();
        $this->assertSame('em_atendimento', $fresh->status);
        $this->assertSame($operator->id, $fresh->assigned_to);
    }

    public function test_status_and_setor_can_be_updated_manually(): void
    {
        Livewire::actingAs($this->operator())
            ->test(AtendimentoShow::class, ['atendimento' => $this->atendimento])
            ->call('atualizarStatus', 'resolvido')
            ->call('atualizarSetor', 'vendas');

        $fresh = $this->atendimento->fresh();
        $this->assertSame('resolvido', $fresh->status);
        $this->assertSame('vendas', $fresh->setor);
        $this->assertNotNull($fresh->resolved_at);
    }
}
