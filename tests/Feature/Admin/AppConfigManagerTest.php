<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\AppConfigManager;
use App\Models\AppConfig;
use App\Models\User;
use App\Services\AppConfigService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class AppConfigManagerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
    }

    public function test_user_without_manage_config_gets_403(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('admin.config.index'))->assertForbidden();
    }

    public function test_admin_can_create_a_config_with_a_plain_value(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        Livewire::actingAs($admin)
            ->test(AppConfigManager::class)
            ->set('key', 'app_name')
            ->set('value', 'Channelly IA')
            ->call('save');

        $this->assertDatabaseHas('app_configs', ['key' => 'app_name', 'value' => 'Channelly IA']);
    }

    public function test_admin_can_upload_media_for_a_config(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        Livewire::actingAs($admin)
            ->test(AppConfigManager::class)
            ->set('key', 'icon_app')
            ->set('media', UploadedFile::fake()->image('icon.png'))
            ->call('save');

        $config = AppConfig::where('key', 'icon_app')->first();

        $this->assertNotNull($config->media_path);
        Storage::disk('public')->assertExists($config->media_path);
    }

    public function test_editing_a_value_invalidates_the_cache(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $config = AppConfig::create(['key' => 'app_name', 'value' => 'Old Name']);

        /** @var AppConfigService $service */
        $service = app(AppConfigService::class);
        $this->assertSame('Old Name', $service->get('app_name'));

        Livewire::actingAs($admin)
            ->test(AppConfigManager::class)
            ->call('edit', $config->id)
            ->set('value', 'New Name')
            ->call('save');

        $this->assertSame('New Name', $service->get('app_name'));
    }

    public function test_deleting_a_config_removes_its_media_file(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $path = UploadedFile::fake()->image('icon.png')->store('app-configs', 'public');
        $config = AppConfig::create(['key' => 'icon_app', 'media_path' => $path]);

        Livewire::actingAs($admin)
            ->test(AppConfigManager::class)
            ->call('delete', $config->id);

        Storage::disk('public')->assertMissing($path);
        $this->assertDatabaseMissing('app_configs', ['id' => $config->id]);
    }
}
