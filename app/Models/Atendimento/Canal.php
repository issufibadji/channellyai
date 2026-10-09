<?php

namespace App\Models\Atendimento;

use App\Concerns\BelongsToEstabelecimento;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class Canal extends Model implements AuditableContract
{
    use Auditable, BelongsToEstabelecimento;

    protected $table = 'canais';

    public const TIPOS = [
        'whatsapp' => 'WhatsApp',
        'telegram' => 'Telegram',
        'instagram' => 'Instagram',
        'facebook' => 'Facebook',
        'site' => 'Site / Chat',
        'email' => 'E-mail',
    ];

    protected $fillable = ['estabelecimento_id', 'nome', 'tipo', 'ativo', 'configuracao'];

    /** Tipos de canal com um provedor de conexão de bot implementado (ver App\Contracts\CanalConexaoProvider). */
    public const TIPOS_COM_PROVIDER = ['telegram'];

    protected function casts(): array
    {
        return [
            'ativo' => 'boolean',
            'configuracao' => 'encrypted:array',
        ];
    }

    public function atendimentos(): HasMany
    {
        return $this->hasMany(Atendimento::class);
    }
}
