<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentDelivery extends Model
{
    protected $fillable = [
        'sender_id',
        'document_type',
        'document_id',
        'recipients',
        'cc',
        'subject',
        'message',
        'attachment_name',
        'status',
        'failure_message',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'recipients' => 'array',
            'cc' => 'array',
            'sent_at' => 'datetime',
        ];
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }
}
