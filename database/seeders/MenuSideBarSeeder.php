<?php

namespace Database\Seeders;

use App\Models\MenuSideBar;
use Illuminate\Database\Seeder;

class MenuSideBarSeeder extends Seeder
{
    private const GRUPO_ACADEMICO = 'Acadêmico';

    private const GRUPO_ADMINISTRACAO = 'Administração do Sistema';

    /**
     * Seed the application's sidebar menu items.
     */
    public function run(): void
    {
        // Atalhos de uso geral — sem seção, sempre fixos no topo.
        MenuSideBar::query()->updateOrCreate(
            ['route_name' => 'dashboard'],
            ['label' => 'Dashboard', 'group' => null, 'icon' => 'home', 'permission' => null, 'order' => 1],
        );

        MenuSideBar::query()->updateOrCreate(
            ['route_name' => 'notifications.index'],
            ['label' => 'Notificações', 'group' => null, 'icon' => 'bell', 'permission' => null, 'order' => 2],
        );

        // Regra de negócio (domínio Acadêmico) — fica no topo da sidebar.
        MenuSideBar::query()->updateOrCreate(
            ['route_name' => 'academico.cursos.index'],
            ['label' => 'Cursos', 'group' => self::GRUPO_ACADEMICO, 'icon' => 'academic-cap', 'permission' => 'manage-cursos', 'order' => 3],
        );

        MenuSideBar::query()->updateOrCreate(
            ['route_name' => 'academico.turmas.index'],
            ['label' => 'Turmas', 'group' => self::GRUPO_ACADEMICO, 'icon' => 'rectangle-group', 'permission' => 'manage-turmas', 'order' => 4],
        );

        MenuSideBar::query()->updateOrCreate(
            ['route_name' => 'academico.minhas-turmas.index'],
            ['label' => 'Minhas Turmas', 'group' => self::GRUPO_ACADEMICO, 'icon' => 'rectangle-group', 'permission' => 'manage-own-turmas', 'order' => 5],
        );

        MenuSideBar::query()->updateOrCreate(
            ['route_name' => 'academico.meus-alunos.index'],
            ['label' => 'Meus Alunos', 'group' => self::GRUPO_ACADEMICO, 'icon' => 'user-group', 'permission' => 'manage-own-turmas', 'order' => 6],
        );

        MenuSideBar::query()->updateOrCreate(
            ['route_name' => 'academico.minha-turma.index'],
            ['label' => 'Minha Turma', 'group' => self::GRUPO_ACADEMICO, 'icon' => 'academic-cap', 'permission' => 'view-own-turma', 'order' => 7],
        );

        // Core / infraestrutura transversal — fica abaixo, é assunto de manutenção do sistema.
        MenuSideBar::query()->updateOrCreate(
            ['route_name' => 'admin.users.index'],
            ['label' => 'Usuários', 'group' => self::GRUPO_ADMINISTRACAO, 'icon' => 'users', 'permission' => 'view-users', 'order' => 8],
        );

        MenuSideBar::query()->updateOrCreate(
            ['route_name' => 'admin.menu.index'],
            ['label' => 'Menu', 'group' => self::GRUPO_ADMINISTRACAO, 'icon' => 'bars-3', 'permission' => 'manage-menu', 'order' => 9],
        );

        MenuSideBar::query()->updateOrCreate(
            ['route_name' => 'admin.roles.index'],
            ['label' => 'Papéis', 'group' => self::GRUPO_ADMINISTRACAO, 'icon' => 'identification', 'permission' => 'manage-roles', 'order' => 10],
        );

        MenuSideBar::query()->updateOrCreate(
            ['route_name' => 'admin.permissions.index'],
            ['label' => 'Permissões', 'group' => self::GRUPO_ADMINISTRACAO, 'icon' => 'key', 'permission' => 'manage-permissions', 'order' => 11],
        );

        MenuSideBar::query()->updateOrCreate(
            ['route_name' => 'admin.roles-user.index'],
            ['label' => 'Vínculo Papéis/Usuários', 'group' => self::GRUPO_ADMINISTRACAO, 'icon' => 'link', 'permission' => 'manage-roles', 'order' => 12],
        );

        MenuSideBar::query()->updateOrCreate(
            ['route_name' => 'admin.config.index'],
            ['label' => 'Configurações', 'group' => self::GRUPO_ADMINISTRACAO, 'icon' => 'cog-6-tooth', 'permission' => 'manage-config', 'order' => 13],
        );

        MenuSideBar::query()->updateOrCreate(
            ['route_name' => 'admin.audits.index'],
            ['label' => 'Auditorias', 'group' => self::GRUPO_ADMINISTRACAO, 'icon' => 'clipboard-document-list', 'permission' => 'view-audits', 'order' => 14],
        );

        MenuSideBar::query()->updateOrCreate(
            ['route_name' => 'admin.announcements.index'],
            ['label' => 'Anúncios', 'group' => self::GRUPO_ADMINISTRACAO, 'icon' => 'megaphone', 'permission' => 'send-notifications', 'order' => 15],
        );

        // Ajuda — utilidade geral, acessível por qualquer autenticado, sem seção.
        MenuSideBar::query()->updateOrCreate(
            ['route_name' => 'ajuda'],
            ['label' => 'Ajuda', 'group' => null, 'icon' => 'question-mark-circle', 'permission' => null, 'order' => 16],
        );

        MenuSideBar::query()->updateOrCreate(
            ['route_name' => 'academico.ao-vivo.index'],
            ['label' => 'Aulas ao Vivo', 'group' => self::GRUPO_ACADEMICO, 'icon' => 'video-camera', 'permission' => 'view-own-turma', 'order' => 18],
        );

        MenuSideBar::query()->updateOrCreate(
            ['route_name' => 'admin.ajuda.index'],
            ['label' => 'Gerenciar Ajuda', 'group' => self::GRUPO_ADMINISTRACAO, 'icon' => 'wrench-screwdriver', 'permission' => 'manage-ajuda', 'order' => 17],
        );
    }
}
