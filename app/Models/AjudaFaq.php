<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AjudaFaq extends Model
{
    use HasFactory;

    protected $fillable = ['role', 'icone', 'pergunta', 'resposta', 'ordem', 'ativo'];

    protected function casts(): array
    {
        return [
            'ativo' => 'boolean',
        ];
    }

    public function scopeAtivo(Builder $query): Builder
    {
        return $query->where('ativo', true);
    }

    public function scopeParaRole(Builder $query, string $role): Builder
    {
        return $query->where('role', $role)->orderBy('ordem');
    }
}
