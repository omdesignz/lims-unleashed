<?php

namespace App\Models;

use Database\Factories\IntegrationConnectorFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class IntegrationConnector extends Model
{
    /** @use HasFactory<IntegrationConnectorFactory> */
    use HasFactory, SoftDeletes;

    public const DIRECTIONS = ['inbound', 'outbound', 'bidirectional'];

    public const ADAPTERS = [
        'rest_json',
        'astm_edge',
        'hl7_edge',
        'csv_sftp',
        'tcp_edge',
        'serial_edge',
        'opc_ua_edge',
    ];

    public const STATUSES = ['draft', 'active', 'paused', 'error'];

    protected $fillable = [
        'inventory_item_id',
        'created_by_id',
        'name',
        'key',
        'direction',
        'adapter',
        'status',
        'health_status',
        'description',
        'configuration',
        'credentials',
        'ingest_token_hash',
        'signing_secret',
        'event_types',
        'last_seen_at',
        'last_tested_at',
        'health_message',
    ];

    protected $hidden = [
        'credentials',
        'ingest_token_hash',
        'signing_secret',
    ];

    protected function casts(): array
    {
        return [
            'configuration' => 'array',
            'credentials' => 'encrypted:array',
            'signing_secret' => 'encrypted',
            'event_types' => 'array',
            'last_seen_at' => 'datetime',
            'last_tested_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $connector): void {
            $connector->uuid ??= (string) Str::uuid();
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function mappings(): HasMany
    {
        return $this->hasMany(IntegrationMapping::class, 'connector_id');
    }

    public function activeMapping(): HasOne
    {
        return $this->hasOne(IntegrationMapping::class, 'connector_id')->where('is_active', true)->latestOfMany();
    }

    public function transmissions(): HasMany
    {
        return $this->hasMany(IntegrationTransmission::class, 'connector_id');
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(IntegrationDelivery::class, 'connector_id');
    }

    public function scopeInbound(Builder $query): Builder
    {
        return $query->whereIn('direction', ['inbound', 'bidirectional']);
    }

    public function scopeOutbound(Builder $query): Builder
    {
        return $query->whereIn('direction', ['outbound', 'bidirectional']);
    }

    public function acceptsInbound(): bool
    {
        return $this->status === 'active' && in_array($this->direction, ['inbound', 'bidirectional'], true);
    }

    public function publishes(string $eventType): bool
    {
        return $this->status === 'active'
            && in_array($this->direction, ['outbound', 'bidirectional'], true)
            && in_array($eventType, $this->event_types ?? [], true);
    }

    public function rotateIngestToken(): string
    {
        $token = Str::random(64);
        $this->forceFill(['ingest_token_hash' => hash('sha256', $token)])->save();

        return $token;
    }

    public function matchesIngestToken(string $token): bool
    {
        return filled($this->ingest_token_hash)
            && hash_equals($this->ingest_token_hash, hash('sha256', $token));
    }
}
