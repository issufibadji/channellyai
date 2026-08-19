<?php

namespace Database\Seeders;

use App\Models\AppConfig;
use Illuminate\Database\Seeder;

class AppConfigSeeder extends Seeder
{
    /**
     * Seed the application's default configuration keys.
     */
    public function run(): void
    {
        AppConfig::query()->updateOrCreate(
            ['key' => 'app_name'],
            [
                'value' => config('app.name'),
                'description' => 'Nome da aplicação exibido na sidebar, telas de login e título das páginas.',
                'required' => true,
            ],
        );

        AppConfig::query()->updateOrCreate(
            ['key' => 'app_logo'],
            [
                'description' => 'Logo/ícone da aplicação (recomendado 128x128, PNG ou SVG). Sem mídia, usa o ícone padrão.',
                'required' => false,
            ],
        );

        AppConfig::query()->updateOrCreate(
            ['key' => 'default_user_avatar'],
            [
                'description' => 'Avatar padrão exibido para usuários sem foto de perfil.',
                'required' => false,
            ],
        );
    }
}
