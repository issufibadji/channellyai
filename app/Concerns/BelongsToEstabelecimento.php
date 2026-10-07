<?php

namespace App\Concerns;

use App\Models\Estabelecimento;
use App\Services\CurrentEstabelecimento;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToEstabelecimento
{
    protected static function bootBelongsToEstabelecimento(): void
    {
        static::addGlobalScope('estabelecimento', function (Builder $builder) {
            $id = app(CurrentEstabelecimento::class)->id();

            $builder->where($builder->getModel()->getTable().'.estabelecimento_id', $id ?? 0);
        });

        static::creating(function ($model) {
            if (! $model->estabelecimento_id) {
                $model->estabelecimento_id = app(CurrentEstabelecimento::class)->id();
            }
        });
    }

    public function estabelecimento(): BelongsTo
    {
        return $this->belongsTo(Estabelecimento::class);
    }
}
