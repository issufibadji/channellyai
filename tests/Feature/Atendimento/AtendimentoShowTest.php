<?php

namespace Tests\Feature\Atendimento;

use App\Livewire\Atendimento\AtendimentoShow;
use App\Models\Atendimento\Atendimento;
use App\Models\Atendimento\Canal;
use App\Models\Atendimento\ChatbotRegra;
use App\Models\Atendimento\Cliente;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AtendimentoShowTest extends TestCase
{
    use RefreshDatabase;

    protected Atendimento $atendimento;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

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

    public function test_client_message_triggers_a_matching_chatbot_rule(): void
    {
        ChatbotRegra::create(['gatilho' => 'boleto', 'resposta' => 'Aqui está seu boleto.', 'ativo' => true]);

        Livewire::actingAs($this->operator())
            ->test(AtendimentoShow::class, ['atendimento' => $this->atendimento])
            ->set('remetente', 'cliente')
            ->set('mensagem', 'Preciso da segunda via do boleto')
            ->call('enviarMensagem');

        $this->assertDatabaseHas('atendimento_mensagens', ['remetente' => 'cliente', 'conteudo' => 'Preciso da segunda via do boleto']);
        $this->assertDatabaseHas('atendimento_mensagens', ['remetente' => 'ia', 'conteudo' => 'Aqui está seu boleto.']);
    }

    public function test_client_message_without_matching_rule_transfers_to_outros(): void
    {
        Livewire::actingAs($this->operator())
            ->test(AtendimentoShow::class, ['atendimento' => $this->atendimento])
            ->set('remetente', 'cliente')
            ->set('mensagem', 'algo bem específico e não mapeado')
            ->call('enviarMensagem');

        $this->assertSame('outros', $this->atendimento->fresh()->setor);
        $this->assertSame('aguardando', $this->atendimento->fresh()->status);
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

    public function test_client_message_is_not_auto_answered_once_a_human_has_taken_over(): void
    {
        ChatbotRegra::create(['gatilho' => 'boleto', 'resposta' => 'Aqui está seu boleto.', 'ativo' => true]);
        $this->atendimento->update(['status' => 'em_atendimento']);

        Livewire::actingAs($this->operator())
            ->test(AtendimentoShow::class, ['atendimento' => $this->atendimento])
            ->set('remetente', 'cliente')
            ->set('mensagem', 'boleto por favor')
            ->call('enviarMensagem');

        $this->assertDatabaseMissing('atendimento_mensagens', ['remetente' => 'ia']);
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
