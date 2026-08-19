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

        MenuSideBar::query()->updateOrCreate(
            ['route_name' => 'atendimento.dashboard'],
            ['label' => 'Atendimento IA', 'icon' => 'chat-bubble-left-right', 'permission' => 'view-atendimentos', 'order' => 11],
        );

        MenuSideBar::query()->updateOrCreate(
            ['route_name' => 'atendimento.index'],
            ['label' => 'Atendimentos', 'icon' => 'inbox', 'permission' => 'view-atendimentos', 'order' => 12],
        );

        MenuSideBar::query()->updateOrCreate(
            ['route_name' => 'atendimento.clientes.index'],
            ['label' => 'Clientes', 'icon' => 'user-group', 'permission' => 'view-clientes', 'order' => 13],
        );

        MenuSideBar::query()->updateOrCreate(
            ['route_name' => 'atendimento.canais.index'],
            ['label' => 'Canais', 'icon' => 'signal', 'permission' => 'manage-canais', 'order' => 14],
        );

        MenuSideBar::query()->updateOrCreate(
            ['route_name' => 'atendimento.chatbot.index'],
            ['label' => 'IA e Chatbot', 'icon' => 'cpu-chip', 'permission' => 'manage-chatbot', 'order' => 15],
        );

        MenuSideBar::query()->updateOrCreate(
            ['route_name' => 'atendimento.relatorios.index'],
            ['label' => 'Relatórios', 'icon' => 'chart-bar', 'permission' => 'view-relatorios-atendimento', 'order' => 16],
        );
    }
}
