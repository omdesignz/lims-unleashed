<?php

namespace App\Models;

use App\Services\SharedDocumentDeliveryAccess;
use App\Support\ShareableDocumentRegistry;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class DocumentDelivery extends Model
{
    protected $attributes = ['status' => 'queued'];

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
            'dispatch_started_at' => 'datetime',
        ];
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function lab(): BelongsTo
    {
        return $this->belongsTo(VAPLab::class, 'lab_id');
    }

    protected static function booted(): void
    {
        static::creating(function (DocumentDelivery $delivery): void {
            $definition = app(ShareableDocumentRegistry::class)->definition($delivery->document_type);
            if (! $definition) {
                throw new LogicException('Unsupported document delivery source.');
            }
            $document = $definition['model']::query()->withoutGlobalScope('financial_laboratory')->findOrFail($delivery->document_id);
            $access = app(SharedDocumentDeliveryAccess::class);
            $delivery->lab_id ??= $access->owner($document);
            $access->assertOwner($document, (int) $delivery->lab_id);
        });
        static::updating(function (DocumentDelivery $delivery): void {
            if ($delivery->isDirty(['lab_id', 'sender_id', 'document_type', 'document_id', 'recipients', 'cc', 'subject', 'message'])) {
                throw new LogicException('Queued document identity and recipient instructions are immutable.');
            }
        });
    }
}
