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
            'manage-matriculas',
            'manage-own-turmas',
            'view-own-turma',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission);
        }

        $admin = Role::findOrCreate('admin');
        $admin->givePermissionTo($permissions);

        // Manager é um perfil acima do professor (tipo diretor) — gerencia
        // cursos, turmas e matrículas de qualquer professor, e também pode
        // ter turmas próprias, já que um manager pode acumular a função de
        // professor. Matrícula é exclusiva do manager: um professor "puro"
        // não matricula/desmatricula aluno — só gerencia módulo/conteúdo
        // da própria turma (via manage-own-turmas).
        $manager = Role::findOrCreate('manager');
        $manager->givePermissionTo(['manage-cursos', 'manage-turmas', 'manage-matriculas', 'manage-own-turmas']);

        $professor = Role::findOrCreate('professor');
        $professor->givePermissionTo('manage-own-turmas');

        $aluno = Role::findOrCreate('aluno');
        $aluno->givePermissionTo('view-own-turma');
    }
}
