<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

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
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission);
        }

        $admin = Role::findOrCreate('admin');
        $admin->syncPermissions($permissions);

        $manager = Role::findOrCreate('manager');
        $manager->syncPermissions(['view-users']);

        Role::findOrCreate('operator');
    }
}
