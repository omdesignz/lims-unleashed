<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationPreference extends Model
{
    protected $fillable = [
        'user_id',
        'category',
        'database_enabled',
        'broadcast_enabled',
        'mail_enabled',
        'quiet_hours_start',
        'quiet_hours_end',
        'timezone',
    ];

    protected function casts(): array
    {
        return [
            'database_enabled' => 'boolean',
            'broadcast_enabled' => 'boolean',
            'mail_enabled' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
