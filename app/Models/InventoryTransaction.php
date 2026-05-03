<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class InventoryTransaction extends Model
{
    use SoftDeletes, HasFactory;

    public const MENU_NAME = 'itransactions';
    
    //
    protected $table = 'itransactions';

    protected $guarded = [];

        /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        
    ];

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

    public function getTransactionTypeAttribute()
    {
        return $this->type->name;
    }

    public function getIsAdditionAttribute()
    {
        return in_array($this->type->code, ['stock_in', 'stock_adjustment_add']);
    }

    public function getIsDeductionAttribute()
    {
        return in_array($this->type->code, ['stock_out', 'stock_adjustment_remove', 'consumption']);
    }

}
