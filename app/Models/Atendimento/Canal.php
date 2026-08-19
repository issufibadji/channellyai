<?php

namespace App\Models\Atendimento;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class Canal extends Model implements AuditableContract
{
    use Auditable;

    protected $table = 'canais';

    public const TIPOS = [
        'whatsapp' => 'WhatsApp',
        'instagram' => 'Instagram',
        'facebook' => 'Facebook',
        'site' => 'Site / Chat',
        'email' => 'E-mail',
    ];

    protected $fillable = ['nome', 'tipo', 'ativo', 'configuracao'];

    protected function casts(): array
    {
        return [
            'ativo' => 'boolean',
            'configuracao' => 'array',
        ];
    }

    public function atendimentos(): HasMany
    {
        return $this->hasMany(Atendimento::class);
    }
}
