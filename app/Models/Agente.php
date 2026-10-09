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
        'estabelecimento_id', 'nome', 'template', 'group_folder', 'webhook_token',
        'agent_group_id', 'status_publicacao', 'publicado_em', 'ultimo_commit',
        'pareamento_pairing_id', 'pareamento_status', 'pareamento_codigo', 'pareado_em',
    ];

    protected $auditExclude = ['webhook_token'];

    protected function casts(): array
    {
        return [
            'publicado_em' => 'datetime',
            'pareado_em' => 'datetime',
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

            if (! $agente->webhook_token) {
                $agente->webhook_token = static::gerarWebhookToken();
            }
        });
    }

    public static function gerarWebhookToken(): string
    {
        return Str::random(40);
    }

    public function regenerarWebhookToken(): void
    {
        $this->update(['webhook_token' => static::gerarWebhookToken()]);
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
