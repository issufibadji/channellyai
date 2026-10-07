<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['clientes', 'canais', 'atendimentos'] as $tabela) {
            Schema::table($tabela, function (Blueprint $table) {
                $table->unsignedBigInteger('estabelecimento_id')->nullable(false)->change();
                $table->foreign('estabelecimento_id')->references('id')->on('estabelecimentos')->cascadeOnDelete();
                $table->index('estabelecimento_id');
            });
        }
    }

    public function down(): void
    {
        foreach (['clientes', 'canais', 'atendimentos'] as $tabela) {
            Schema::table($tabela, function (Blueprint $table) {
                $table->dropForeign(['estabelecimento_id']);
                $table->dropIndex([$tabela.'_estabelecimento_id_index']);
                $table->unsignedBigInteger('estabelecimento_id')->nullable()->change();
            });
        }
    }
};
