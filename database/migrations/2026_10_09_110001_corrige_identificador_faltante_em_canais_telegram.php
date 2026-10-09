<?php

use App\Models\Atendimento\Canal;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Canal::withoutGlobalScope('estabelecimento')
            ->where('tipo', 'telegram')
            ->get()
            ->each(function (Canal $canal) {
                $config = $canal->configuracao;

                if ($config && ! empty($config['username']) && empty($config['identificador'])) {
                    $canal->update(['configuracao' => $config + ['identificador' => '@'.$config['username']]]);
                }
            });
    }

    public function down(): void
    {
        // Correção de dado — nada a reverter.
    }
};
