<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class AgenteDadosNegocio extends Model implements AuditableContract
{
    use Auditable;

    protected $table = 'agente_dados_negocio';

    protected $fillable = [
        'agente_id', 'nome_exibicao', 'tom_de_voz', 'endereco', 'horario_funcionamento',
        'antecedencia_minima', 'calendar_id', 'profissionais', 'politicas',
        'contato_humano', 'mensagem_encaminhamento',
    ];

    protected function casts(): array
    {
        return [
            'politicas' => 'array',
        ];
    }

    public function agente(): BelongsTo
    {
        return $this->belongsTo(Agente::class);
    }
}
