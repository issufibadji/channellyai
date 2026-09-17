<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Curso extends Model
{
    use HasFactory;

    protected $fillable = ['nome', 'tipo', 'descricao', 'ativo'];

    protected function casts(): array
    {
        return [
            'ativo' => 'boolean',
        ];
    }

    public function turmas(): HasMany
    {
        return $this->hasMany(Turma::class);
    }
}
