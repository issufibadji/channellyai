<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class MenuSideBar extends Model implements AuditableContract
{
    use Auditable;

    protected $fillable = ['parent_id', 'label', 'group', 'icon', 'route_name', 'permission', 'order'];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('order');
    }

    public function scopeRoots($query)
    {
        return $query->whereNull('parent_id')->orderBy('order');
    }

    public function hasValidIcon(): bool
    {
        return (bool) $this->icon
            && file_exists(base_path("vendor/blade-ui-kit/blade-heroicons/resources/svg/o-{$this->icon}.svg"));
    }
}
