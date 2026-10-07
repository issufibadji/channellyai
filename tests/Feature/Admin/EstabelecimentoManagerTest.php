<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\EstabelecimentoManager;
use App\Models\Estabelecimento;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class EstabelecimentoManagerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_user_without_manage_estabelecimentos_gets_403(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('admin.estabelecimentos.index'))->assertForbidden();
    }

    public function test_admin_can_create_an_estabelecimento(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        Livewire::actingAs($admin)
            ->test(EstabelecimentoManager::class)
            ->set('nome', 'Barbearia do Zé')
            ->set('slug', 'barbearia-do-ze')
            ->set('tipoNegocio', 'barbearia')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('estabelecimentos', [
            'nome' => 'Barbearia do Zé',
            'slug' => 'barbearia-do-ze',
            'tipo_negocio' => 'barbearia',
        ]);
    }

    public function test_slug_duplicado_e_rejeitado(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        Estabelecimento::create(['nome' => 'Existente', 'slug' => 'existente', 'tipo_negocio' => 'outro']);

        Livewire::actingAs($admin)
            ->test(EstabelecimentoManager::class)
            ->set('nome', 'Novo')
            ->set('slug', 'existente')
            ->set('tipoNegocio', 'outro')
            ->call('save')
            ->assertHasErrors('slug');
    }

    public function test_slug_com_formato_invalido_e_rejeitado(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        Livewire::actingAs($admin)
            ->test(EstabelecimentoManager::class)
            ->set('nome', 'Novo')
            ->set('slug', 'Slug Inválido!')
            ->set('tipoNegocio', 'outro')
            ->call('save')
            ->assertHasErrors('slug');
    }

    public function test_slug_nao_pode_ser_alterado_na_edicao(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $estabelecimento = Estabelecimento::create(['nome' => 'Original', 'slug' => 'original', 'tipo_negocio' => 'outro']);

        Livewire::actingAs($admin)
            ->test(EstabelecimentoManager::class)
            ->call('edit', $estabelecimento->id)
            ->set('slug', 'tentativa-de-mudanca')
            ->set('nome', 'Nome Atualizado')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('original', $estabelecimento->fresh()->slug);
        $this->assertSame('Nome Atualizado', $estabelecimento->fresh()->nome);
    }

    public function test_admin_can_vincular_usuarios(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $operator = User::factory()->create();

        Livewire::actingAs($admin)
            ->test(EstabelecimentoManager::class)
            ->set('nome', 'Consultório X')
            ->set('slug', 'consultorio-x')
            ->set('tipoNegocio', 'consultorio')
            ->set('usuarios', [$operator->id])
            ->call('save');

        $estabelecimento = Estabelecimento::where('slug', 'consultorio-x')->first();

        $this->assertTrue($estabelecimento->usuarios->contains($operator));
    }

    public function test_admin_can_delete_an_estabelecimento(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $estabelecimento = Estabelecimento::create(['nome' => 'Remover', 'slug' => 'remover', 'tipo_negocio' => 'outro']);

        Livewire::actingAs($admin)
            ->test(EstabelecimentoManager::class)
            ->call('delete', $estabelecimento->id);

        $this->assertDatabaseMissing('estabelecimentos', ['id' => $estabelecimento->id]);
    }
}
