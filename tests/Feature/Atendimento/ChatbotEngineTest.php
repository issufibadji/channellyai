<?php

namespace Tests\Feature\Atendimento;

use App\Models\Atendimento\Atendimento;
use App\Models\Atendimento\Canal;
use App\Models\Atendimento\ChatbotRegra;
use App\Models\Atendimento\Cliente;
use App\Services\Atendimento\ChatbotEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatbotEngineTest extends TestCase
{
    use RefreshDatabase;

    private function atendimento(): Atendimento
    {
        return Atendimento::create([
            'cliente_id' => Cliente::create(['nome' => 'Maria'])->id,
            'canal_id' => Canal::create(['nome' => 'WhatsApp', 'tipo' => 'whatsapp'])->id,
            'status' => 'aberto',
        ]);
    }

    public function test_matches_case_insensitively_by_substring(): void
    {
        ChatbotRegra::create(['gatilho' => 'Boleto', 'resposta' => 'Segue o boleto.', 'ativo' => true]);

        $resposta = (new ChatbotEngine)->responder($this->atendimento(), 'Preciso de um BOLETO urgente');

        $this->assertSame('Segue o boleto.', $resposta->resposta);
    }

    public function test_ignores_inactive_rules(): void
    {
        ChatbotRegra::create(['gatilho' => 'boleto', 'resposta' => 'Não deveria responder.', 'ativo' => false]);

        $resposta = (new ChatbotEngine)->responder($this->atendimento(), 'quero um boleto');

        $this->assertNotSame('Não deveria responder.', $resposta->resposta);
        $this->assertSame('outros', $resposta->setorTransferencia);
    }

    public function test_respects_rule_order_when_multiple_rules_match(): void
    {
        ChatbotRegra::create(['gatilho' => 'boleto', 'resposta' => 'Segunda regra.', 'ativo' => true, 'order' => 2]);
        ChatbotRegra::create(['gatilho' => 'segunda via', 'resposta' => 'Primeira regra.', 'ativo' => true, 'order' => 1]);

        $resposta = (new ChatbotEngine)->responder($this->atendimento(), 'quero a segunda via do boleto');

        $this->assertSame('Primeira regra.', $resposta->resposta);
    }

    public function test_falls_back_to_outros_when_nothing_matches(): void
    {
        $resposta = (new ChatbotEngine)->responder($this->atendimento(), 'mensagem sem gatilho cadastrado');

        $this->assertSame('outros', $resposta->setorTransferencia);
    }
}
