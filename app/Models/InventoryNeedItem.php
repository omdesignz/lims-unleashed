<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class InventoryNeedItem extends Model
{
    protected $fillable = [
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
            'estimated_unit_price' => 'decimal:2',
        ];
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
