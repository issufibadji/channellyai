<?php

namespace App\Models\Atendimento;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class Atendimento extends Model implements AuditableContract
{
    use Auditable;

    protected $table = 'atendimentos';

    public const STATUSES = [
        'aberto' => 'Aberto',
        'em_atendimento' => 'Em atendimento',
        'aguardando' => 'Aguardando',
        'resolvido' => 'Resolvido',
    ];

    public const SETORES = [
        'vendas' => 'Vendas',
        'suporte' => 'Suporte',
        'financeiro' => 'Financeiro',
        'logistica' => 'Logística',
        'outros' => 'Outros',
    ];

    protected $fillable = ['cliente_id', 'canal_id', 'assigned_to', 'setor', 'status', 'satisfacao', 'resolved_at'];

    protected function casts(): array
    {
        return [
            'resolved_at' => 'datetime',
        ];
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function canal(): BelongsTo
    {
        return $this->belongsTo(Canal::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function mensagens(): HasMany
    {
        return $this->hasMany(AtendimentoMensagem::class)->orderBy('created_at');
    }
}
