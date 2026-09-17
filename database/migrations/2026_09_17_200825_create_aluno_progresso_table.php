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
        Schema::create('aluno_progresso', function (Blueprint $table) {
            $table->id();
            $table->foreignId('aluno_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('conteudo_id')->constrained('conteudos')->cascadeOnDelete();
            $table->timestamp('concluido_em');
            $table->timestamps();

            $table->unique(['aluno_id', 'conteudo_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('aluno_progresso');
    }
};
