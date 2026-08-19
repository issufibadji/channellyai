<?php

namespace App\Models\Atendimento;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class ChatbotRegra extends Model implements AuditableContract
{
    use Auditable;

    protected $table = 'chatbot_regras';

    protected $fillable = ['gatilho', 'resposta', 'setor_transferencia', 'ativo', 'order'];

    protected function casts(): array
    {
        return [
            'ativo' => 'boolean',
        ];
    }
}
