<?php

namespace App\Models;

use App\Models\Atendimento\Atendimento;
use App\Models\Atendimento\Canal;
use App\Models\Atendimento\Cliente;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class Estabelecimento extends Model implements AuditableContract
{
    use Auditable, HasFactory;

    public const TIPOS_NEGOCIO = [
        'barbearia' => 'Barbearia',
        'consultorio' => 'Consultório',
        'consultoria' => 'Consultoria',
        'outro' => 'Outro',
    ];

    protected $fillable = ['nome', 'slug', 'tipo_negocio', 'ativo'];

    protected function casts(): array
    {
        return [
            'ativo' => 'boolean',
        ];
    }

    public function usuarios(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'estabelecimento_user');
    }

    public function agentes(): HasMany
    {
        return $this->hasMany(Agente::class);
    }

    public function clientes(): HasMany
    {
        return $this->hasMany(Cliente::class);
    }

    public function canais(): HasMany
    {
        return $this->hasMany(Canal::class);
    }

    public function atendimentos(): HasMany
    {
        return $this->hasMany(Atendimento::class);
    }
}
