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
        Schema::table('aulas_ao_vivo', function (Blueprint $table) {
            // Vídeo da turma criado a partir da gravação da aula.
            $table->foreignId('conteudo_id')->nullable()->after('turma_id')->constrained('conteudos')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('aulas_ao_vivo', function (Blueprint $table) {
            $table->dropConstrainedForeignId('conteudo_id');
        });
    }
};
