<?php

namespace App\Models;

use Database\Factories\IntegrationDeliveryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class IntegrationDelivery extends Model
{
    /** @use HasFactory<IntegrationDeliveryFactory> */
    use HasFactory;

    protected $fillable = [
        'connector_id',
        'event_type',
        'subject_type',
        'subject_id',
        'idempotency_key',
        'payload',
        'status',
        'attempts',
        'http_status',
        'response_excerpt',
        'last_error',
        'next_attempt_at',
        'delivered_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'next_attempt_at' => 'datetime',
            'delivered_at' => 'datetime',
        ];
    }

    public function connector(): BelongsTo
    {
        return $this->belongsTo(IntegrationConnector::class, 'connector_id');
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }
}
