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
                $table->foreignId('estabelecimento_id')->nullable()->after('id');
            });
        }
    }

    public function down(): void
    {
        foreach (['clientes', 'canais', 'atendimentos'] as $tabela) {
            Schema::table($tabela, function (Blueprint $table) {
                $table->dropColumn('estabelecimento_id');
            });
        }
    }
};
