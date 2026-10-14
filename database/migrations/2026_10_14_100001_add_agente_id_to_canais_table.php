<?php

use App\Models\Agente;
use App\Models\Atendimento\Canal;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('canais', function (Blueprint $table) {
            $table->foreignId('agente_id')->nullable()->after('estabelecimento_id')->constrained('agentes')->nullOnDelete();
        });

        // Hoje existe no máximo 1 Agente por estabelecimento — associa os canais
        // já conectados ao agente do mesmo estabelecimento, sem perder histórico.
        Canal::withoutGlobalScope('estabelecimento')->whereNull('agente_id')->get()->each(function (Canal $canal) {
            $agente = Agente::where('estabelecimento_id', $canal->estabelecimento_id)->first();

            if ($agente) {
                $canal->update(['agente_id' => $agente->id]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('canais', function (Blueprint $table) {
            $table->dropConstrainedForeignId('agente_id');
        });
    }
};
