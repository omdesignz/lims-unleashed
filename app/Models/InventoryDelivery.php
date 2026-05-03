<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class InventoryDelivery extends Model
{
    use HasFactory, SoftDeletes;

    public CONST MENU_NAME = 'ideliveries';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'sales_date',
        'customer_id',
    ];

    protected $table = 'i_deliveries';
    protected $dates = ['created_at', 'updated_at', 'deleted_at'];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        
    ];

    /**
     * Inventory Delivery Customer
     *
     * @return Relationship
     */
    public function customer() {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

     /**
     * Items
     *
     * @return Relationship
     */
    public function items()
    {
        return $this->hasMany(InventoryDeliveryDetail::class, 'delivery_id');
    }

}
