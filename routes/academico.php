<?php

use App\Livewire\Academico\AlunoManager;
use App\Livewire\Academico\CursoManager;
use App\Livewire\Academico\MatriculaManager;
use App\Livewire\Academico\MinhasTurmas;
use App\Livewire\Academico\MinhasTurmasDoAluno;
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
        Route::get('academico/turmas/{turma}/matricula', MatriculaManager::class)->name('academico.turmas.matricula');
    });

    Route::middleware('checkPermission:view-own-turma')
        ->get('academico/minha-turma', MinhasTurmasDoAluno::class)->name('academico.minha-turma.index');
});
