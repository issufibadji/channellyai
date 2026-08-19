<?php

namespace App\Models\Atendimento;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AtendimentoMensagem extends Model
{
    protected $table = 'atendimento_mensagens';

    public const REMETENTES = ['cliente', 'ia', 'atendente'];

    protected $fillable = ['atendimento_id', 'autor_id', 'remetente', 'conteudo'];

    public function atendimento(): BelongsTo
    {
        return $this->belongsTo(Atendimento::class);
    }

    public function autor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'autor_id');
    }
}
