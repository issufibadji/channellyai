<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AlunoRespostaOpcao extends Model
{
    protected $table = 'aluno_resposta_opcoes';

    protected $fillable = ['aluno_id', 'opcao_id'];

    public function aluno(): BelongsTo
    {
        return $this->belongsTo(User::class, 'aluno_id');
    }

    public function opcao(): BelongsTo
    {
        return $this->belongsTo(ExercicioOpcao::class, 'opcao_id');
    }
}
