<?php

namespace App\Models;

use App\Enums\Orders\InventoryOrderTrackingStatus;
use HighSolutions\EloquentSequence\Sequence;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class InventoryOrder extends Model
{
    use HasFactory, SoftDeletes, Sequence;

    public CONST MENU_NAME = 'iorders';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'date',
        'supplier_id',
        'user_id',
        'order_year',
        'seq',
        'reference',
        'status',
        'obs',
        'currency',
        'total_amount',
    ];

    protected $table = 'i_orders';
    protected $dates = ['created_at', 'updated_at', 'deleted_at'];
    

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'status' => InventoryOrderTrackingStatus::class,
    ];

    public function sequence()
    {
        return [
            'group' => ['order_year', 'seq'],
            'fieldName' => 'seq',
        ];
    }

    /**
     * Inventory Item Supplier
     *
     * @return Relationship
     */
    public function supplier() {
        return $this->belongsTo(InventoryItemSupplier::class, 'supplier_id');
    }

    /**
     * User
     *
     * @return Relationship
     */
    public function user() {
        return $this->belongsTo(User::class, 'user_id');
    }

     /**
     * Items
     *
     * @return Relationship
     */
    //  public function items()
    // {
    //     return $this->belongsToMany(InventoryItem::class, 'i_order_details', 'order_id', 'item_id')
    //         ->withPivot('qty', 'expected_date', 'actual_date', 'warehouse_id', 'status')
    //         ->withTimestamps();
    // }

    public function items()
    {
        return $this->hasMany(InventoryOrderDetail::class, 'order_id');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    public function getTotalItemsAttribute()
    {
        return $this->details()->sum('qty');
    }

    public function getTotalValueAttribute()
    {
        // You might want to add price to order details
        return 0;
    }

    public function ratings()
    {
        return $this->morphMany(Rating::class, 'rateable');
    }

    public static function boot()
    {
        parent::boot();

            static::creating(function($order) {

                $order->reference = 'PO-' . $order->order_year . '/' . str_pad ($order->seq, 4, '0', STR_PAD_LEFT);
        
            });

    }

}
