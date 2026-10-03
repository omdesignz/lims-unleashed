<?php

namespace App\Models;

use App\Enums\Orders\InventoryOrderItemStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use LogicException;

class InventoryOrderDetail extends Model
{
    use HasFactory, SoftDeletes;

    public const MENU_NAME = 'iorderdetails';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'lab_id',
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
    ];

    protected $attributes = [
        'received_qty' => 0,
        'unit_price' => 0,
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
        'qty' => 'decimal:4',
        'received_qty' => 'decimal:4',
        'unit_price' => 'decimal:4',
        'total_price' => 'decimal:4',
    ];

    protected static function booted(): void
    {
        static::creating(function (InventoryOrderDetail $detail): void {
            $detail->lab_id = InventoryOrder::query()->findOrFail($detail->order_id)->lab_id;
        });

        static::updating(function (InventoryOrderDetail $detail): void {
            if ($detail->isDirty(['lab_id', 'order_id'])) {
                throw new LogicException('A purchase-order line cannot change its owning laboratory or order.');
            }
        });
    }

    /**
     * Inventory Order
     *
     * @return Relationship
     */
    public function order()
    {
        return $this->belongsTo(InventoryOrder::class, 'order_id');
    }

    /**
     * Inventory Item Warehouse
     *
     * @return Relationship
     */
    public function warehouse()
    {
        return $this->belongsTo(InventoryItemWarehouse::class, 'warehouse_id');
    }

    /**
     * Inventory Item
     *
     * @return Relationship
     */
    public function item()
    {
        return $this->belongsTo(InventoryItem::class, 'item_id');
    }
}
