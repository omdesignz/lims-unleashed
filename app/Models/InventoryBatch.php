<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;

class InventoryBatch extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'i_inventory_batches';

    protected $fillable = [
        'lab_id',
        'inventory_id',
        'batch_number',
        'qty_received',
        'qty_remaining',
        'expiry_date',
        'received_date',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $batch): void {
            if ($batch->lab_id === null) {
                $batch->lab_id = Inventory::query()->findOrFail($batch->inventory_id)->lab_id;
            }
        });

        static::updating(function (self $batch): void {
            if ($batch->isDirty(['lab_id', 'inventory_id'])) {
                throw new LogicException('Inventory batch ownership cannot be reassigned.');
            }
        });
    }

    protected function casts(): array
    {
        return [
            'qty_received' => 'decimal:4',
            'qty_remaining' => 'decimal:4',
            'expiry_date' => 'date',
            'received_date' => 'date',
        ];
    }

    public function inventory(): BelongsTo
    {
        return $this->belongsTo(Inventory::class, 'inventory_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(InventoryTransaction::class, 'batch_id');
    }

    public function consumptions(): HasMany
    {
        return $this->hasMany(ReagentConsumption::class, 'batch_id');
    }
}
