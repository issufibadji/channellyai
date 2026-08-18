<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SystemAnnouncement extends Model
{
    protected $fillable = ['title', 'message', 'target', 'channels', 'sent_by'];

    protected function casts(): array
    {
        return [
            'channels' => 'array',
        ];
    }

    public function sentBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by');
    }
}
