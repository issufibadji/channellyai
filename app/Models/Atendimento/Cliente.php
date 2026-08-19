<?php

namespace App\Models\Atendimento;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class Cliente extends Model implements AuditableContract
{
    use Auditable;

    protected $table = 'clientes';

    protected $fillable = ['nome', 'email', 'telefone', 'documento', 'notas'];

    public function atendimentos(): HasMany
    {
        return $this->hasMany(Atendimento::class);
    }
}
