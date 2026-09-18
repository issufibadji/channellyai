<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AjudaSeeder extends Seeder
{
    /**
     * Seed the initial help content (ajudas and ajuda_faqs).
     */
    public function run(): void
    {
        $now = now();

        $ajudas = [
            'admin' => 'Use o menu lateral para gerenciar usuários, papéis e permissões, '
                .'configurações da aplicação, o menu do sistema, auditorias e anúncios. '
                .'Em caso de dúvida sobre alguma tela específica, fale com o time de desenvolvimento.',
            'professor' => 'Use o menu lateral para acompanhar suas turmas, gerenciar módulos e '
                .'conteúdos das aulas, matricular ou desmatricular alunos e consultar os alunos '
                .'das suas turmas. Em caso de dúvida, fale com a administração.',
            'aluno' => 'Aqui você encontra respostas para as dúvidas mais comuns sobre a plataforma. '
                .'Se não encontrar o que procura, fale com seu professor ou administrador.',
        ];

        foreach ($ajudas as $role => $conteudo) {
            DB::table('ajudas')->updateOrInsert(
                ['role' => $role],
                ['conteudo' => $conteudo, 'updated_at' => $now, 'created_at' => $now],
            );
        }

        $faqs = [
            [
                'icone' => 'play',
                'pergunta' => 'Como assistir às aulas?',
                'resposta' => 'Clique em "Minha Turma" no menu lateral, escolha a turma, depois o '
                    .'módulo e por fim o conteúdo que deseja acessar.',
                'ordem' => 1,
            ],
            [
                'icone' => 'chart-bar',
                'pergunta' => 'Como acompanhar meu progresso?',
                'resposta' => 'Seu progresso é registrado quando você marca uma aula como concluída — '
                    .'clique no botão "Marcar como concluído" ao final de cada conteúdo. No Dashboard '
                    .'e em "Minha Turma" você acompanha o percentual concluído de cada turma.',
                'ordem' => 2,
            ],
            [
                'icone' => 'arrow-down-tray',
                'pergunta' => 'Como baixar os materiais de apoio?',
                'resposta' => 'Cada conteúdo do tipo PDF já é o próprio material da aula — clique nele '
                    .'para abrir ou baixar.',
                'ordem' => 3,
            ],
            [
                'icone' => 'wifi',
                'pergunta' => 'Posso acessar o conteúdo sem internet?',
                'resposta' => 'Não, é necessário estar conectado à internet para acessar vídeos e '
                    .'demais conteúdos da plataforma.',
                'ordem' => 4,
            ],
            [
                'icone' => 'device-phone-mobile',
                'pergunta' => 'A plataforma funciona no celular?',
                'resposta' => 'Sim! Você pode acessar pelo navegador do celular normalmente, ou '
                    .'instalar como aplicativo — veja o guia logo abaixo nesta página.',
                'ordem' => 5,
            ],
        ];

        foreach ($faqs as $faq) {
            DB::table('ajuda_faqs')->updateOrInsert(
                ['role' => 'aluno', 'pergunta' => $faq['pergunta']],
                [
                    'icone' => $faq['icone'],
                    'resposta' => $faq['resposta'],
                    'ordem' => $faq['ordem'],
                    'ativo' => true,
                    'updated_at' => $now,
                    'created_at' => $now,
                ],
            );
        }
    }
}
