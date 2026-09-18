<?php

namespace Tests\Feature;

use App\Livewire\AjudaManager;
use App\Models\Ajuda;
use App\Models\AjudaFaq;
use App\Models\User;
use Database\Seeders\AcademicoPermissionSeeder;
use Database\Seeders\AjudaPermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AjudaManagerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->seed(AcademicoPermissionSeeder::class);
        $this->seed(AjudaPermissionSeeder::class);
    }

    public function test_user_without_manage_ajuda_gets_403(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('admin.ajuda.index'))->assertForbidden();
    }

    public function test_admin_can_edit_the_help_text_of_a_role(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $ajuda = Ajuda::create(['role' => 'professor', 'conteudo' => 'Texto antigo.']);

        Livewire::actingAs($admin)
            ->test(AjudaManager::class)
            ->call('editAjuda', $ajuda->id)
            ->set('ajudaConteudo', 'Texto novo.')
            ->call('saveAjuda');

        $this->assertDatabaseHas('ajudas', ['id' => $ajuda->id, 'conteudo' => 'Texto novo.']);
    }

    public function test_admin_can_create_a_faq(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        Livewire::actingAs($admin)
            ->test(AjudaManager::class)
            ->call('createFaq')
            ->set('icone', 'play')
            ->set('pergunta', 'Nova pergunta?')
            ->set('resposta', 'Nova resposta.')
            ->set('ordem', 1)
            ->set('ativo', true)
            ->call('saveFaq');

        $this->assertDatabaseHas('ajuda_faqs', [
            'role' => 'aluno',
            'pergunta' => 'Nova pergunta?',
            'resposta' => 'Nova resposta.',
        ]);
    }

    public function test_admin_can_edit_a_faq(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $faq = AjudaFaq::factory()->create(['pergunta' => 'Pergunta antiga?']);

        Livewire::actingAs($admin)
            ->test(AjudaManager::class)
            ->call('editFaq', $faq->id)
            ->set('pergunta', 'Pergunta editada?')
            ->call('saveFaq');

        $this->assertDatabaseHas('ajuda_faqs', ['id' => $faq->id, 'pergunta' => 'Pergunta editada?']);
    }

    public function test_admin_can_reorder_faqs(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $primeira = AjudaFaq::factory()->create(['pergunta' => 'Primeira?', 'ordem' => 1]);
        $segunda = AjudaFaq::factory()->create(['pergunta' => 'Segunda?', 'ordem' => 2]);

        Livewire::actingAs($admin)
            ->test(AjudaManager::class)
            ->call('moveDown', $primeira->id);

        $this->assertSame(2, $primeira->fresh()->ordem);
        $this->assertSame(1, $segunda->fresh()->ordem);
    }

    public function test_admin_can_toggle_faq_ativo(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $faq = AjudaFaq::factory()->create(['ativo' => true]);

        Livewire::actingAs($admin)
            ->test(AjudaManager::class)
            ->call('toggleAtivo', $faq->id);

        $this->assertFalse($faq->fresh()->ativo);
    }

    public function test_admin_can_delete_a_faq(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $faq = AjudaFaq::factory()->create();

        Livewire::actingAs($admin)
            ->test(AjudaManager::class)
            ->call('deleteFaq', $faq->id);

        $this->assertDatabaseMissing('ajuda_faqs', ['id' => $faq->id]);
    }
}
