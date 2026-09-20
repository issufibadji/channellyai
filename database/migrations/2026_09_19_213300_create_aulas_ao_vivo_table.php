<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('aulas_ao_vivo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('turma_id')->constrained('turmas')->cascadeOnDelete();
            $table->string('titulo');
            $table->text('descricao')->nullable();
            $table->dateTime('inicio_em');
            $table->unsignedSmallInteger('duracao_minutos')->default(60);
            $table->string('sala')->unique();
            $table->string('status')->default('agendada');
            $table->dateTime('iniciada_em')->nullable();
            $table->dateTime('encerrada_em')->nullable();
            $table->timestamps();

            $table->index(['turma_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('aulas_ao_vivo');
    }
};
