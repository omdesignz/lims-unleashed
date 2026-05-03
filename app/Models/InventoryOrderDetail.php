<?php

namespace App\Models;

use App\Enums\Orders\InventoryOrderItemStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class InventoryOrderDetail extends Model
{
    use HasFactory, SoftDeletes;

    public CONST MENU_NAME = 'iorderdetails';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'qty',
        'received_qty',
        'expected_date',
        'actual_date',
        'order_id',
        'item_id',
        'status',
        'warehouse_id',
        'currency',
        'unit_price',
        'total_price',
    ];

    protected $table = 'i_order_details';
    protected $dates = ['created_at', 'updated_at', 'deleted_at'];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'status' => InventoryOrderItemStatus::class,
    ];

    /**
     * Inventory Order
     *
     * @return Relationship
     */
    public function order() {
        return $this->belongsTo(InventoryOrder::class, 'order_id');
    }

    /**
     * Inventory Item Warehouse
     *
     * @return Relationship
     */
    public function warehouse() {
        return $this->belongsTo(InventoryItemWarehouse::class, 'warehouse_id');
    }

    /**
     * Inventory Item
     *
     * @return Relationship
     */
    public function item() {
        return $this->belongsTo(InventoryItem::class, 'item_id');
    }

}
