<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class InventoryNeedItem extends Model
{
    protected $fillable = [
        'lab_id',
        'inventory_need_id',
        'inventory_item_id',
        'warehouse_id',
        'quantity_requested',
        'quantity_approved',
        'quantity_received',
        'estimated_unit_price',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'quantity_requested' => 'decimal:4',
            'quantity_approved' => 'decimal:4',
            'quantity_received' => 'decimal:4',
            'estimated_unit_price' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (InventoryNeedItem $item): void {
            $item->lab_id = InventoryNeed::query()->findOrFail($item->inventory_need_id)->lab_id;
        });

        static::updating(function (InventoryNeedItem $item): void {
            if ($item->isDirty(['lab_id', 'inventory_need_id'])) {
                throw new LogicException('A procurement need line cannot change its owning laboratory or need.');
            }
        });
    }

    public function need(): BelongsTo
    {
        return $this->belongsTo(InventoryNeed::class, 'inventory_need_id');
    }

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(InventoryItemWarehouse::class, 'warehouse_id');
    }
}
