<?php

namespace Database\Seeders;

use App\Models\AlunoProgresso;
use App\Models\Conteudo;
use App\Models\Curso;
use App\Models\Modulo;
use App\Models\Turma;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TestUsersSeeder extends Seeder
{
    /**
     * Seed one login-ready user per role, plus minimal academic data for
     * Professor Teste / Aluno Teste. Local development only.
     */
    public function run(): void
    {
        if (! app()->environment('local')) {
            return;
        }

        $admin = $this->criarUsuario('Admin Teste', 'admin@teste.com', 'admin');
        $this->criarUsuario('Manager Teste', 'manager@teste.com', 'manager');
        $this->criarUsuario('Operator Teste', 'operator@teste.com', 'operator');
        $professor = $this->criarUsuario('Professor Teste', 'professor@teste.com', 'professor');
        $aluno = $this->criarUsuario('Aluno Teste', 'aluno@teste.com', 'aluno');

        $this->seedDadosAcademicos($professor, $aluno);
    }

    private function criarUsuario(string $name, string $email, string $role): User
    {
        $user = User::firstOrNew(['email' => $email]);

        $user->forceFill([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'active' => true,
            'requires_2fa' => false,
        ])->save();

        $user->syncRoles([$role]);

        return $user;
    }

    private function seedDadosAcademicos(User $professor, User $aluno): void
    {
        $curso = Curso::updateOrCreate(
            ['nome' => 'Inglês Teste'],
            ['tipo' => 'curto_prazo', 'descricao' => 'Curso de teste pra ambiente local.', 'ativo' => true],
        );

        $turma = Turma::updateOrCreate(
            ['curso_id' => $curso->id, 'professor_id' => $professor->id, 'nome' => 'Turma Teste'],
            ['data_inicio' => now()->subDays(10)->toDateString(), 'data_fim' => null, 'ativo' => true],
        );

        $turma->alunos()->syncWithoutDetaching([
            $aluno->id => ['data_matricula' => now()->subDays(10)->toDateString(), 'status' => 'ativo'],
        ]);

        $moduloA1 = Modulo::updateOrCreate(
            ['turma_id' => $turma->id, 'nome' => 'A1'],
            ['categoria' => 'nivel', 'nivel' => 'A1', 'ordem' => 1],
        );

        $moduloB1 = Modulo::updateOrCreate(
            ['turma_id' => $turma->id, 'nome' => 'B1'],
            ['categoria' => 'nivel', 'nivel' => 'B1', 'ordem' => 2],
        );

        $moduloExtra = Modulo::updateOrCreate(
            ['turma_id' => $turma->id, 'nome' => 'Atividades Extras'],
            ['categoria' => 'extra', 'nivel' => null, 'ordem' => 3],
        );

        $aula1A1 = Conteudo::updateOrCreate(
            ['modulo_id' => $moduloA1->id, 'titulo' => 'Aula 1 - Cumprimentos'],
            ['tipo' => 'texto', 'corpo' => 'Conteúdo de teste já disponível.', 'ordem' => 1, 'dias_liberacao' => 0, 'bloqueado' => false],
        );

        Conteudo::updateOrCreate(
            ['modulo_id' => $moduloA1->id, 'titulo' => 'Aula 2 - Vocabulário Básico'],
            ['tipo' => 'texto', 'corpo' => 'Outro conteúdo de teste já disponível.', 'ordem' => 2, 'dias_liberacao' => 3, 'bloqueado' => false],
        );

        Conteudo::updateOrCreate(
            ['modulo_id' => $moduloA1->id, 'titulo' => 'Aula 3 - Revisão'],
            ['tipo' => 'texto', 'corpo' => 'Conteúdo de teste bloqueado manualmente.', 'ordem' => 3, 'dias_liberacao' => 0, 'bloqueado' => true],
        );

        Conteudo::updateOrCreate(
            ['modulo_id' => $moduloB1->id, 'titulo' => 'Aula 1 - Conversação Intermediária'],
            ['tipo' => 'texto', 'corpo' => 'Conteúdo de teste já disponível no nível B1.', 'ordem' => 1, 'dias_liberacao' => 0, 'bloqueado' => false],
        );

        Conteudo::updateOrCreate(
            ['modulo_id' => $moduloB1->id, 'titulo' => 'Aula 2 - Gramática Avançada'],
            ['tipo' => 'texto', 'corpo' => 'Conteúdo de teste ainda não liberado.', 'ordem' => 2, 'dias_liberacao' => 15, 'bloqueado' => false],
        );

        Conteudo::updateOrCreate(
            ['modulo_id' => $moduloExtra->id, 'titulo' => 'Podcast recomendado'],
            ['tipo' => 'link', 'url_externa' => 'https://example.com/podcast-ingles', 'ordem' => 1, 'dias_liberacao' => 0, 'bloqueado' => false],
        );

        AlunoProgresso::updateOrCreate(
            ['aluno_id' => $aluno->id, 'conteudo_id' => $aula1A1->id],
            ['concluido_em' => now()->subDays(5)],
        );
    }
}
