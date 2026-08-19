<?php

namespace Database\Seeders;

use App\Models\MenuSideBar;
use Illuminate\Database\Seeder;

class MenuSideBarSeeder extends Seeder
{
    private const GRUPO_ATENDIMENTO = 'Atendimento';

    private const GRUPO_ADMINISTRACAO = 'Administração do Sistema';

    /**
     * Seed the application's sidebar menu items.
     */
    public function run(): void
    {
        // Regra de negócio (domínio de Atendimento com IA) — fica no topo da sidebar.
        MenuSideBar::query()->updateOrCreate(
            ['route_name' => 'atendimento.dashboard'],
            ['label' => 'Atendimento IA', 'group' => self::GRUPO_ATENDIMENTO, 'icon' => 'chat-bubble-left-right', 'permission' => 'view-atendimentos', 'order' => 1],
        );

        MenuSideBar::query()->updateOrCreate(
            ['route_name' => 'atendimento.index'],
            ['label' => 'Atendimentos', 'group' => self::GRUPO_ATENDIMENTO, 'icon' => 'inbox', 'permission' => 'view-atendimentos', 'order' => 2],
        );

        MenuSideBar::query()->updateOrCreate(
            ['route_name' => 'atendimento.clientes.index'],
            ['label' => 'Clientes', 'group' => self::GRUPO_ATENDIMENTO, 'icon' => 'user-group', 'permission' => 'view-clientes', 'order' => 3],
        );

        MenuSideBar::query()->updateOrCreate(
            ['route_name' => 'atendimento.canais.index'],
            ['label' => 'Canais', 'group' => self::GRUPO_ATENDIMENTO, 'icon' => 'signal', 'permission' => 'manage-canais', 'order' => 4],
        );

        MenuSideBar::query()->updateOrCreate(
            ['route_name' => 'atendimento.chatbot.index'],
            ['label' => 'IA e Chatbot', 'group' => self::GRUPO_ATENDIMENTO, 'icon' => 'cpu-chip', 'permission' => 'manage-chatbot', 'order' => 5],
        );

        MenuSideBar::query()->updateOrCreate(
            ['route_name' => 'atendimento.relatorios.index'],
            ['label' => 'Relatórios', 'group' => self::GRUPO_ATENDIMENTO, 'icon' => 'chart-bar', 'permission' => 'view-relatorios-atendimento', 'order' => 6],
        );

        // Core / infraestrutura transversal — fica abaixo, é assunto de manutenção do sistema.
        MenuSideBar::query()->updateOrCreate(
            ['route_name' => 'dashboard'],
            ['label' => 'Dashboard', 'group' => self::GRUPO_ADMINISTRACAO, 'icon' => 'home', 'permission' => null, 'order' => 7],
        );

        MenuSideBar::query()->updateOrCreate(
            ['route_name' => 'notifications.index'],
            ['label' => 'Notificações', 'group' => self::GRUPO_ADMINISTRACAO, 'icon' => 'bell', 'permission' => null, 'order' => 8],
        );

        MenuSideBar::query()->updateOrCreate(
            ['route_name' => 'admin.users.index'],
            ['label' => 'Usuários', 'group' => self::GRUPO_ADMINISTRACAO, 'icon' => 'users', 'permission' => 'view-users', 'order' => 9],
        );

        MenuSideBar::query()->updateOrCreate(
            ['route_name' => 'admin.menu.index'],
            ['label' => 'Menu', 'group' => self::GRUPO_ADMINISTRACAO, 'icon' => 'bars-3', 'permission' => 'manage-menu', 'order' => 10],
        );

        MenuSideBar::query()->updateOrCreate(
            ['route_name' => 'admin.roles.index'],
            ['label' => 'Papéis', 'group' => self::GRUPO_ADMINISTRACAO, 'icon' => 'identification', 'permission' => 'manage-roles', 'order' => 11],
        );

        MenuSideBar::query()->updateOrCreate(
            ['route_name' => 'admin.permissions.index'],
            ['label' => 'Permissões', 'group' => self::GRUPO_ADMINISTRACAO, 'icon' => 'key', 'permission' => 'manage-permissions', 'order' => 12],
        );

        MenuSideBar::query()->updateOrCreate(
            ['route_name' => 'admin.roles-user.index'],
            ['label' => 'Vínculo Papéis/Usuários', 'group' => self::GRUPO_ADMINISTRACAO, 'icon' => 'link', 'permission' => 'manage-roles', 'order' => 13],
        );

        MenuSideBar::query()->updateOrCreate(
            ['route_name' => 'admin.config.index'],
            ['label' => 'Configurações', 'group' => self::GRUPO_ADMINISTRACAO, 'icon' => 'cog-6-tooth', 'permission' => 'manage-config', 'order' => 14],
        );

        MenuSideBar::query()->updateOrCreate(
            ['route_name' => 'admin.audits.index'],
            ['label' => 'Auditorias', 'group' => self::GRUPO_ADMINISTRACAO, 'icon' => 'clipboard-document-list', 'permission' => 'view-audits', 'order' => 15],
        );

        MenuSideBar::query()->updateOrCreate(
            ['route_name' => 'admin.announcements.index'],
            ['label' => 'Anúncios', 'group' => self::GRUPO_ADMINISTRACAO, 'icon' => 'megaphone', 'permission' => 'send-notifications', 'order' => 16],
        );
    }
}
