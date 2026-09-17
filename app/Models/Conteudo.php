<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Conteudo extends Model
{
    use HasFactory;

    protected $fillable = ['modulo_id', 'titulo', 'tipo', 'corpo', 'arquivo_path', 'url_externa', 'ordem'];

    public function modulo(): BelongsTo
    {
        return $this->belongsTo(Modulo::class);
    }
}
