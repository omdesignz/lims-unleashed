<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class InventoryDeliveryDetail extends Model
{
    use HasFactory, SoftDeletes;

    public CONST MENU_NAME = 'ideliverydetails';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'qty',
        'expected_date',
        'actual_date',
        'delivery_id',
        'item_id',
        'warehouse_id',
    ];

    protected $table = 'i_delivery_details';
    protected $dates = ['created_at', 'updated_at', 'deleted_at'];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        
    ];

    /**
     * Inventory Deloivery
     *
     * @return Relationship
     */
    public function delivery() {
        return $this->belongsTo(InventoryDelivery::class, 'delivery_id');
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
