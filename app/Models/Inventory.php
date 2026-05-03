<?php

namespace App\Models;

use App\Enums\Orders\InventoryItemStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class Inventory extends Model
{
    use HasFactory, SoftDeletes;

    public CONST MENU_NAME = 'inventory';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'item_id',
        'name',
        'warehouse_id',
        'qty_available',
        'min_stock_level',
        'reorder_point',
        'status',
        'category_id',
    ];

    protected $table = 'inventory';
    protected $dates = ['created_at', 'updated_at', 'deleted_at'];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'status' => InventoryItemStatus::class,
        'qty_available' => 'integer',
        'min_stock_level' => 'integer',
        'reorder_point' => 'integer',
        'deleted_at' => 'datetime',
    ];

    /**
     * Inventory Item
     *
     * @return Relationship
     */
    public function item() {
        return $this->belongsTo(InventoryItem::class, 'item_id');
    }

    /**
     * Inventory Item Category
     *
     * @return Relationship
     */
    public function category() {
        return $this->belongsTo(ItemCategory::class, 'category_id');
    }

    /**
     * Inventory Warehouse
     *
     * @return Relationship
     */
    public function warehouse() {
        return $this->belongsTo(InventoryItemWarehouse::class, 'warehouse_id');
    }

    public function transactions()
    {
        return $this->hasMany(InventoryTransaction::class, 'inventory_id');
    }

    public function scopeLowStock($query)
    {
        return $query->whereColumn('qty_available', '<=', 'reorder_point');
    }

    public function scopeOutOfStock($query)
    {
        return $query->where('qty_available', '<=', 0);
    }

    public function scopeInStock($query)
    {
        return $query->where('qty_available', '>', 0);
    }

    public function getStockStatusAttribute()
    {
        if ($this->qty_available <= 0) {
            return 'out_of_stock';
        } elseif ($this->qty_available <= $this->reorder_point) {
            return 'low_stock';
        } elseif ($this->qty_available <= $this->min_stock_level) {
            return 'critical_stock';
        } else {
            return 'in_stock';
        }
    }

    public function getStockStatusColorAttribute()
    {
        return match($this->stock_status) {
            'out_of_stock' => 'red',
            'critical_stock' => 'orange',
            'low_stock' => 'yellow',
            'in_stock' => 'green',
            default => 'gray',
        };
    }

    public function getStockStatusLabelAttribute()
    {
        return match($this->stock_status) {
            'out_of_stock' => 'Out of Stock',
            'critical_stock' => 'Critical',
            'low_stock' => 'Low Stock',
            'in_stock' => 'In Stock',
            default => 'Unknown',
        };
    }

    // Deduct Stock
    public function deductStock($quantity) {
        $this->qty_available -= $quantity;
        $this->save();
    }

}
