<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use LogicException;

class InventoryTransaction extends Model
{
    use HasFactory, SoftDeletes;

    public const MENU_NAME = 'itransactions';

    public const ADDITION_CODES = ['stock_in', 'stock_adjustment_add', 'consumption_reversal', 'RECEIPT'];

    public const DEDUCTION_CODES = ['stock_out', 'stock_adjustment_remove', 'consumption'];

    //
    protected $table = 'itransactions';

    protected $guarded = [];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'qty' => 'decimal:4',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $transaction): void {
            if ($transaction->lab_id === null) {
                $transaction->lab_id = Inventory::query()->findOrFail($transaction->inventory_id)->lab_id;
            }
        });

        static::updating(function (self $transaction): void {
            if ($transaction->isDirty(['lab_id', 'inventory_id', 'item_id', 'warehouse_id', 'batch_id'])) {
                throw new LogicException('Inventory ledger identity cannot be reassigned.');
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Inventory
     *
     * @return Relationship
     */
    public function inventory(): BelongsTo
    {
        return $this->belongsTo(Inventory::class, 'inventory_id');
    }

    /**
     * Inventory Item
     *
     * @return Relationship
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'item_id');
    }

    /**
     * Inventory Warehouse
     *
     * @return Relationship
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(InventoryItemWarehouse::class, 'warehouse_id');
    }

    /**
     * Inventory Transaction Type
     *
     * @return Relationship
     */
    public function type(): BelongsTo
    {
        return $this->belongsTo(InventoryTransactionType::class, 'type_id');
    }

    public function scopeRecent($query, $limit = 50)
    {
        return $query->orderBy('created_at', 'desc')->limit($limit);
    }

    public function scopeForLaboratory(Builder $query, int $labId): Builder
    {
        return $query->where('itransactions.lab_id', $labId);
    }

    public function getTransactionTypeAttribute()
    {
        return $this->type->name;
    }

    public function getIsAdditionAttribute(): bool
    {
        return in_array($this->type?->code, self::ADDITION_CODES, true);
    }

    public function getIsDeductionAttribute(): bool
    {
        return in_array($this->type?->code, self::DEDUCTION_CODES, true);
    }
}
