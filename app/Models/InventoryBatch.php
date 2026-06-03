<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryBatch extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'i_inventory_batches';

    protected $fillable = [
        'inventory_id',
        'batch_number',
        'qty_received',
        'qty_remaining',
        'expiry_date',
        'received_date',
    ];

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
