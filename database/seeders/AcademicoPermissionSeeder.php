<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class AcademicoPermissionSeeder extends Seeder
{
    /**
     * Seed the roles and permissions for the Domínio Acadêmico.
     */
    public function run(): void
    {
        $permissions = [
            'manage-cursos',
            'manage-turmas',
            'manage-own-turmas',
            'view-own-turma',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission);
        }

        $admin = Role::findOrCreate('admin');
        $admin->givePermissionTo($permissions);

        $professor = Role::findOrCreate('professor');
        $professor->givePermissionTo('manage-own-turmas');

        $aluno = Role::findOrCreate('aluno');
        $aluno->givePermissionTo('view-own-turma');
    }
}
