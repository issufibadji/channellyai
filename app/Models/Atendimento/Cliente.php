<?php

namespace App\Models\Atendimento;

use App\Concerns\BelongsToEstabelecimento;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class Cliente extends Model implements AuditableContract
{
    use Auditable, BelongsToEstabelecimento;

    protected $table = 'clientes';

    protected $fillable = ['estabelecimento_id', 'nome', 'email', 'telefone', 'documento', 'notas'];

    public function atendimentos(): HasMany
    {
        return $this->hasMany(Atendimento::class);
    }
}
