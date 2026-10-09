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
        Agente::whereNotNull('telegram_bot_token')->each(function (Agente $agente) {
            $canal = Canal::withoutGlobalScope('estabelecimento')->firstOrCreate(
                ['estabelecimento_id' => $agente->estabelecimento_id, 'tipo' => 'telegram'],
                ['nome' => 'Telegram (agente)', 'ativo' => true],
            );

            $canal->update([
                'configuracao' => [
                    'token' => $agente->telegram_bot_token,
                    'username' => $agente->telegram_bot_username,
                    'identificador' => '@'.$agente->telegram_bot_username,
                ],
            ]);
        });

        Schema::table('agentes', function (Blueprint $table) {
            $table->dropColumn(['telegram_bot_token', 'telegram_bot_username']);
        });
    }

    public function down(): void
    {
        Schema::table('agentes', function (Blueprint $table) {
            $table->text('telegram_bot_token')->nullable()->after('agent_group_id');
            $table->string('telegram_bot_username')->nullable()->after('telegram_bot_token');
        });

        Canal::withoutGlobalScope('estabelecimento')->where('tipo', 'telegram')->whereNotNull('configuracao')->each(function (Canal $canal) {
            $agente = Agente::where('estabelecimento_id', $canal->estabelecimento_id)->first();

            if ($agente && $canal->configuracao) {
                $agente->update([
                    'telegram_bot_token' => $canal->configuracao['token'] ?? null,
                    'telegram_bot_username' => $canal->configuracao['username'] ?? null,
                ]);
            }
        });
    }
};
