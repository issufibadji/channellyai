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
        Schema::create('turma_aluno', function (Blueprint $table) {
            $table->id();
            $table->foreignId('turma_id')->constrained('turmas')->cascadeOnDelete();
            $table->foreignId('aluno_id')->constrained('users')->cascadeOnDelete();
            $table->date('data_matricula');
            $table->enum('status', ['ativo', 'trancado', 'concluido'])->default('ativo');
            $table->timestamps();

            $table->unique(['turma_id', 'aluno_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('turma_aluno');
    }
};
