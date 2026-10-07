<?php

use App\Livewire\Atendimento\AtendimentoDashboard;
use App\Livewire\Atendimento\AtendimentoManager;
use App\Livewire\Atendimento\AtendimentoShow;
use App\Livewire\Atendimento\CanalManager;
use App\Livewire\Atendimento\ChatbotManager;
use App\Livewire\Atendimento\ClienteManager;
use App\Livewire\Atendimento\Relatorios;
use App\Livewire\Atendimento\SemEstabelecimento;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'check2fa'])->prefix('atendimento')->name('atendimento.')->group(function () {
    Route::get('sem-estabelecimento', SemEstabelecimento::class)->name('sem-estabelecimento');

    Route::middleware('checkPermission:view-atendimentos')->group(function () {
        Route::middleware('setEstabelecimento')->group(function () {
            Route::get('/', AtendimentoDashboard::class)->name('dashboard');
            Route::get('lista', AtendimentoManager::class)->name('index');
        });
    });

    Route::middleware(['checkPermission:view-clientes', 'setEstabelecimento'])
        ->get('clientes/lista', ClienteManager::class)->name('clientes.index');

    Route::middleware(['checkPermission:manage-canais', 'setEstabelecimento'])
        ->get('canais', CanalManager::class)->name('canais.index');

    Route::middleware(['checkPermission:manage-chatbot', 'setEstabelecimento'])
        ->get('chatbot', ChatbotManager::class)->name('chatbot.index');

    Route::middleware(['checkPermission:view-relatorios-atendimento', 'setEstabelecimento'])
        ->get('relatorios', Relatorios::class)->name('relatorios.index');

    // Curinga — precisa vir por último, senão "engole" as rotas estáticas acima.
    Route::middleware(['checkPermission:view-atendimentos', 'setEstabelecimento'])
        ->get('{atendimento}', AtendimentoShow::class)->name('show');
});
