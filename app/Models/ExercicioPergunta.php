<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExercicioPergunta extends Model
{
    use HasFactory;

    protected $fillable = ['conteudo_id', 'enunciado', 'ordem'];

    public function conteudo(): BelongsTo
    {
        return $this->belongsTo(Conteudo::class);
    }

    public function opcoes(): HasMany
    {
        return $this->hasMany(ExercicioOpcao::class, 'pergunta_id')->orderBy('ordem');
    }
}
