<?php

use App\Livewire\Academico\Aluno\Aula;
use App\Livewire\Academico\Aluno\MinhasTurmasLista;
use App\Livewire\Academico\Aluno\ModuloConteudos;
use App\Livewire\Academico\Aluno\TurmaModulos;
use App\Livewire\Academico\AlunoManager;
use App\Livewire\Academico\CursoManager;
use App\Livewire\Academico\MatriculaManager;
use App\Livewire\Academico\MinhasTurmas;
use App\Livewire\Academico\ModuloConteudoManager;
use App\Livewire\Academico\TurmaManager;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'check2fa'])->group(function () {
    Route::middleware('checkPermission:manage-cursos')
        ->get('academico/cursos', CursoManager::class)->name('academico.cursos.index');

    Route::middleware('checkPermission:manage-turmas')
        ->get('academico/turmas', TurmaManager::class)->name('academico.turmas.index');

    Route::middleware('checkPermission:manage-own-turmas')->group(function () {
        Route::get('academico/minhas-turmas', MinhasTurmas::class)->name('academico.minhas-turmas.index');
        Route::get('academico/meus-alunos', AlunoManager::class)->name('academico.meus-alunos.index');
        Route::get('academico/turmas/{turma}/conteudo', ModuloConteudoManager::class)->name('academico.turmas.conteudo');
    });

    Route::middleware('checkPermission:manage-matriculas')
        ->get('academico/turmas/{turma}/matricula', MatriculaManager::class)->name('academico.turmas.matricula');

    Route::middleware('checkPermission:view-own-turma')->group(function () {
        Route::get('academico/minha-turma', MinhasTurmasLista::class)->name('academico.minha-turma.index');
        Route::get('academico/minha-turma/{turma}', TurmaModulos::class)->name('academico.minha-turma.turma');
        Route::get('academico/minha-turma/{turma}/modulo/{modulo}', ModuloConteudos::class)->name('academico.minha-turma.modulo');
        Route::get('academico/minha-turma/{turma}/aula/{conteudo}', Aula::class)->name('academico.minha-turma.aula');
    });
});
