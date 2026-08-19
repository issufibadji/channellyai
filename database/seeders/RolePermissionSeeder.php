<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    /**
     * Seed the application's roles and permissions.
     */
    public function run(): void
    {
        $permissions = [
            'view-users',
            'create-users',
            'edit-users',
            'delete-users',
            'manage-menu',
            'manage-roles',
            'manage-permissions',
            'manage-config',
            'view-audits',
            'send-notifications',
            'view-atendimentos',
            'manage-atendimentos',
            'view-clientes',
            'manage-clientes',
            'manage-canais',
            'manage-chatbot',
            'view-relatorios-atendimento',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission);
        }

        $admin = Role::findOrCreate('admin');
        $admin->syncPermissions($permissions);

        $manager = Role::findOrCreate('manager');
        $manager->syncPermissions(['view-users', 'view-atendimentos', 'view-clientes', 'view-relatorios-atendimento']);

        $operator = Role::findOrCreate('operator');
        $operator->syncPermissions(['view-atendimentos', 'manage-atendimentos', 'view-clientes', 'manage-clientes']);
    }
}
