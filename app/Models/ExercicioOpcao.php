<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExercicioOpcao extends Model
{
    use HasFactory;

    protected $table = 'exercicio_opcoes';

    protected $fillable = ['pergunta_id', 'texto', 'correta', 'ordem'];

    protected function casts(): array
    {
        return [
            'correta' => 'boolean',
        ];
    }

    public function pergunta(): BelongsTo
    {
        return $this->belongsTo(ExercicioPergunta::class, 'pergunta_id');
    }
}
