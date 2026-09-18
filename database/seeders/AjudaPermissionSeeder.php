<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class AjudaPermissionSeeder extends Seeder
{
    /**
     * Seed the permission for managing help content.
     */
    public function run(): void
    {
        Permission::findOrCreate('manage-ajuda');

        $admin = Role::findOrCreate('admin');
        $admin->givePermissionTo('manage-ajuda');
    }
}
