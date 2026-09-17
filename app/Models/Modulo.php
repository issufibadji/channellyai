<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Modulo extends Model
{
    use HasFactory;

    protected $fillable = ['turma_id', 'nome', 'nivel', 'ordem'];

    public function turma(): BelongsTo
    {
        return $this->belongsTo(Turma::class);
    }

    public function conteudos(): HasMany
    {
        return $this->hasMany(Conteudo::class)->orderBy('ordem');
    }
}
