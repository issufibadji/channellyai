<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class AppConfig extends Model implements AuditableContract
{
    use Auditable;

    protected $fillable = ['key', 'value', 'description', 'media_path', 'required'];

    protected function casts(): array
    {
        return [
            'required' => 'boolean',
        ];
    }
}
