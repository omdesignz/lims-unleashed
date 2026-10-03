<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class BroadcastNotification extends Model
{
    use HasFactory;

    protected $fillable = [
        'lab_id',
        'sender_id',
        'title',
        'message',
        'type',
        'priority',
        'recipient_type',
        'recipient_count',
        'scheduled_at',
        'expires_at',
    ];

    protected $casts = [
        'lab_id' => 'integer',
        'scheduled_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::updating(function (self $notification): void {
            if ($notification->isDirty('lab_id')) {
                throw new LogicException('A broadcast notification cannot change laboratories.');
            }
        });
    }

    public function laboratory(): BelongsTo
    {
        return $this->belongsTo(VAPLab::class, 'lab_id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }
}
