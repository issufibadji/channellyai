<?php

namespace Database\Seeders;

use App\Models\MenuSideBar;
use Illuminate\Database\Seeder;

class MenuSideBarSeeder extends Seeder
{
    /**
     * Seed the application's sidebar menu items.
     */
    public function run(): void
    {
        MenuSideBar::query()->updateOrCreate(
            ['route_name' => 'dashboard'],
            ['label' => 'Dashboard', 'icon' => 'home', 'permission' => null, 'order' => 1],
        );

        MenuSideBar::query()->updateOrCreate(
            ['route_name' => 'notifications.index'],
            ['label' => 'Notificações', 'icon' => 'bell', 'permission' => null, 'order' => 2],
        );

        MenuSideBar::query()->updateOrCreate(
            ['route_name' => 'admin.users.index'],
            ['label' => 'Usuários', 'icon' => 'users', 'permission' => 'view-users', 'order' => 3],
        );

        MenuSideBar::query()->updateOrCreate(
            ['route_name' => 'admin.menu.index'],
            ['label' => 'Menu', 'icon' => 'bars-3', 'permission' => 'manage-menu', 'order' => 4],
        );

        MenuSideBar::query()->updateOrCreate(
            ['route_name' => 'admin.roles.index'],
            ['label' => 'Papéis', 'icon' => 'identification', 'permission' => 'manage-roles', 'order' => 5],
        );

        MenuSideBar::query()->updateOrCreate(
            ['route_name' => 'admin.permissions.index'],
            ['label' => 'Permissões', 'icon' => 'key', 'permission' => 'manage-permissions', 'order' => 6],
        );

        MenuSideBar::query()->updateOrCreate(
            ['route_name' => 'admin.roles-user.index'],
            ['label' => 'Vínculo Papéis/Usuários', 'icon' => 'link', 'permission' => 'manage-roles', 'order' => 7],
        );

        MenuSideBar::query()->updateOrCreate(
            ['route_name' => 'admin.config.index'],
            ['label' => 'Configurações', 'icon' => 'cog-6-tooth', 'permission' => 'manage-config', 'order' => 8],
        );

        MenuSideBar::query()->updateOrCreate(
            ['route_name' => 'admin.audits.index'],
            ['label' => 'Auditorias', 'icon' => 'clipboard-document-list', 'permission' => 'view-audits', 'order' => 9],
        );

        MenuSideBar::query()->updateOrCreate(
            ['route_name' => 'admin.announcements.index'],
            ['label' => 'Anúncios', 'icon' => 'megaphone', 'permission' => 'send-notifications', 'order' => 10],
        );
    }
}
