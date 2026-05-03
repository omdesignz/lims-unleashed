<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\AsCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class CustomerRequest extends Model
{
    use HasFactory, SoftDeletes;

    public const MENU_NAME = 'customer_requests';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'reference',
        'title',
        'request_type',
        'status',
        'priority',
        'preferred_date',
        'submitted_at',
        'resolved_at',
        'description',
        'contact',
        'email',
        'category_id',
        'customer_id',
        'answered',
        'warehouse_id',
        'extra_data',
    ];

    protected $table = 'customer_requests';
    protected $dates = ['created_at', 'updated_at', 'deleted_at'];


    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'extra_data' => 'array',
        'answered' => 'boolean',
        'submitted_at' => 'datetime',
        'resolved_at' => 'datetime',
        'preferred_date' => 'date',
        'extra_data' => AsCollection::class
    ];

    /**
     * Customer Request Category
     *
     * @return Relationship
     */
    public function category()
    {
        return $this->belongsTo(CustomerRequestCategory::class, 'category_id');
    }

    /**
     * Customer
     *
     * @return Relationship
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    /**
     * Warehouse
     *
     * @return Relationship
     */
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function getPortalStatusAttribute(): string
    {
        if ($this->status) {
            return $this->status;
        }

        return $this->answered ? 'completed' : 'pending';
    }
}
