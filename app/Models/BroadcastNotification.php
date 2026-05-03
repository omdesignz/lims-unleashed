<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BroadcastNotification extends Model
{
    use HasFactory;

    protected $fillable = [
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
        'scheduled_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }
}