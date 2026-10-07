<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class AgenteServico extends Model implements AuditableContract
{
    use Auditable;

    protected $table = 'agente_servicos';

    protected $fillable = ['agente_id', 'nome', 'duracao_minutos', 'preco'];

    protected function casts(): array
    {
        return [
            'preco' => 'decimal:2',
        ];
    }

    public function agente(): BelongsTo
    {
        return $this->belongsTo(Agente::class);
    }
}
