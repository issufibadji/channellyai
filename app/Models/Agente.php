<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class Agente extends Model implements AuditableContract
{
    use Auditable;

    public const STATUS_PUBLICACAO = [
        'rascunho' => 'Rascunho',
        'publicado' => 'Publicado',
        'erro' => 'Erro',
    ];

    protected $fillable = [
        'estabelecimento_id', 'nome', 'template', 'group_folder',
        'agent_group_id', 'status_publicacao', 'publicado_em', 'ultimo_commit',
    ];

    protected function casts(): array
    {
        return [
            'publicado_em' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $agente) {
            if (! $agente->group_folder) {
                $agente->group_folder = $agente->estabelecimento?->slug ?? (string) Str::uuid();
            }

            if (! $agente->status_publicacao) {
                $agente->status_publicacao = 'rascunho';
            }
        });
    }

    public function estabelecimento(): BelongsTo
    {
        return $this->belongsTo(Estabelecimento::class);
    }

    public function dadosNegocio(): HasOne
    {
        return $this->hasOne(AgenteDadosNegocio::class);
    }

    public function servicos(): HasMany
    {
        return $this->hasMany(AgenteServico::class);
    }
}
