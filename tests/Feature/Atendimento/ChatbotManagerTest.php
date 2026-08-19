<?php

namespace Tests\Feature\Atendimento;

use App\Livewire\Atendimento\ChatbotManager;
use App\Models\Atendimento\ChatbotRegra;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ChatbotManagerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_user_without_manage_chatbot_gets_403(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('atendimento.chatbot.index'))->assertForbidden();
    }

    public function test_admin_can_create_a_regra(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        Livewire::actingAs($admin)
            ->test(ChatbotManager::class)
            ->set('gatilho', 'boleto')
            ->set('resposta', 'Aqui está o link do boleto.')
            ->set('setorTransferencia', 'financeiro')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('chatbot_regras', ['gatilho' => 'boleto', 'setor_transferencia' => 'financeiro']);
    }

    public function test_invalid_setor_is_rejected(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        Livewire::actingAs($admin)
            ->test(ChatbotManager::class)
            ->set('gatilho', 'boleto')
            ->set('setorTransferencia', 'setor-invalido')
            ->call('save')
            ->assertHasErrors('setorTransferencia');
    }

    public function test_admin_can_delete_a_regra(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $regra = ChatbotRegra::create(['gatilho' => 'faq']);

        Livewire::actingAs($admin)
            ->test(ChatbotManager::class)
            ->call('delete', $regra->id);

        $this->assertDatabaseMissing('chatbot_regras', ['id' => $regra->id]);
    }
}
