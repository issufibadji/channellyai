<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AlunoProgresso extends Model
{
    use HasFactory;

    protected $table = 'aluno_progresso';

    protected $fillable = ['aluno_id', 'conteudo_id', 'concluido_em'];

    protected function casts(): array
    {
        return [
            'concluido_em' => 'datetime',
        ];
    }

    public function aluno(): BelongsTo
    {
        return $this->belongsTo(User::class, 'aluno_id');
    }

    public function conteudo(): BelongsTo
    {
        return $this->belongsTo(Conteudo::class);
    }
}
