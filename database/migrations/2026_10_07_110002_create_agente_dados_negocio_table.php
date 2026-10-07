<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agente_dados_negocio', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agente_id')->unique()->constrained('agentes')->cascadeOnDelete();
            $table->string('nome_exibicao')->nullable();
            $table->string('tom_de_voz')->nullable();
            $table->string('endereco')->nullable();
            $table->string('horario_funcionamento')->nullable();
            $table->string('antecedencia_minima')->nullable();
            $table->string('calendar_id')->nullable();
            $table->text('profissionais')->nullable();
            $table->json('politicas')->nullable();
            $table->string('contato_humano')->nullable();
            $table->text('mensagem_encaminhamento')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agente_dados_negocio');
    }
};
