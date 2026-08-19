<?php

namespace Tests\Feature;

use App\Models\AppConfig;
use Database\Seeders\AppConfigSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AppConfigHelperTest extends TestCase
{
    use RefreshDatabase;

    public function test_config_app_returns_the_stored_value(): void
    {
        AppConfig::create(['key' => 'app_name', 'value' => 'Minha Empresa']);

        $this->assertSame('Minha Empresa', config_app('app_name'));
    }

    public function test_config_app_falls_back_to_default_when_missing(): void
    {
        $this->assertSame('Fallback', config_app('nao_existe', 'Fallback'));
    }

    public function test_config_app_media_returns_public_url_for_uploaded_file(): void
    {
        Storage::fake('public');
        $path = UploadedFile::fake()->create('logo.png')->store('app-configs', 'public');
        AppConfig::create(['key' => 'app_logo', 'media_path' => $path]);

        $this->assertSame(Storage::disk('public')->url($path), config_app_media('app_logo'));
    }

    public function test_config_app_media_returns_null_without_media(): void
    {
        $this->assertNull(config_app_media('app_logo'));
    }

    public function test_login_page_displays_the_configured_app_name(): void
    {
        AppConfig::create(['key' => 'app_name', 'value' => 'Channelly Custom']);

        $this->get(route('login'))->assertSee('Channelly Custom');
    }

    public function test_app_config_seeder_creates_the_expected_default_keys(): void
    {
        $this->seed(AppConfigSeeder::class);

        $this->assertDatabaseHas('app_configs', ['key' => 'app_name']);
        $this->assertDatabaseHas('app_configs', ['key' => 'app_logo']);
        $this->assertDatabaseHas('app_configs', ['key' => 'default_user_avatar']);
    }
}
