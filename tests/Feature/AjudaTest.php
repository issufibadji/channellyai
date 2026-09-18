<?php

namespace Tests\Feature;

use App\Models\Ajuda;
use App\Models\AjudaFaq;
use App\Models\User;
use Database\Seeders\AcademicoPermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AjudaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->seed(AcademicoPermissionSeeder::class);
    }

    public function test_admin_sees_the_help_text_for_their_role(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        Ajuda::create(['role' => 'admin', 'conteudo' => 'Texto de ajuda do admin.']);
        Ajuda::create(['role' => 'professor', 'conteudo' => 'Texto de ajuda do professor.']);

        Livewire::actingAs($admin)
            ->test(\App\Livewire\Ajuda::class)
            ->assertSee('Texto de ajuda do admin.')
            ->assertDontSee('Texto de ajuda do professor.');
    }

    public function test_professor_sees_the_help_text_for_their_role(): void
    {
        $professor = User::factory()->create();
        $professor->assignRole('professor');

        Ajuda::create(['role' => 'admin', 'conteudo' => 'Texto de ajuda do admin.']);
        Ajuda::create(['role' => 'professor', 'conteudo' => 'Texto de ajuda do professor.']);

        Livewire::actingAs($professor)
            ->test(\App\Livewire\Ajuda::class)
            ->assertSee('Texto de ajuda do professor.')
            ->assertDontSee('Texto de ajuda do admin.');
    }

    public function test_aluno_sees_banner_faq_in_order_and_pwa_guide(): void
    {
        $aluno = User::factory()->create();
        $aluno->assignRole('aluno');

        Ajuda::create(['role' => 'aluno', 'conteudo' => 'Bem-vindo à central de ajuda do aluno.']);

        AjudaFaq::factory()->create(['pergunta' => 'Primeira pergunta?', 'ordem' => 1]);
        AjudaFaq::factory()->create(['pergunta' => 'Segunda pergunta?', 'ordem' => 2]);
        AjudaFaq::factory()->create(['pergunta' => 'Terceira pergunta?', 'ordem' => 3]);
        AjudaFaq::factory()->create(['pergunta' => 'Quarta pergunta?', 'ordem' => 4]);
        AjudaFaq::factory()->create(['pergunta' => 'Quinta pergunta?', 'ordem' => 5]);

        $component = Livewire::actingAs($aluno)->test(\App\Livewire\Ajuda::class);

        $component
            ->assertSee('Bem-vindo à central de ajuda do aluno.')
            ->assertSeeInOrder([
                'Primeira pergunta?',
                'Segunda pergunta?',
                'Terceira pergunta?',
                'Quarta pergunta?',
                'Quinta pergunta?',
            ])
            ->assertSee('Instalar como aplicativo')
            ->assertSee('Adicionar à Tela de Início')
            ->assertSee('Adicionar à tela inicial');
    }

    public function test_inactive_faq_is_not_shown_to_aluno(): void
    {
        $aluno = User::factory()->create();
        $aluno->assignRole('aluno');

        AjudaFaq::factory()->create(['pergunta' => 'Pergunta ativa?', 'ativo' => true]);
        AjudaFaq::factory()->inativo()->create(['pergunta' => 'Pergunta inativa?']);

        Livewire::actingAs($aluno)
            ->test(\App\Livewire\Ajuda::class)
            ->assertSee('Pergunta ativa?')
            ->assertDontSee('Pergunta inativa?');
    }

    public function test_route_is_reachable_by_any_authenticated_user_without_a_specific_permission(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('ajuda'))->assertOk();
    }
}
