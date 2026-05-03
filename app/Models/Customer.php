<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class Customer extends Model
{
    use HasFactory, SoftDeletes;

    public CONST MENU_NAME = 'customers';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'description',
        'code',
        'category_id',
        'warehouse_id',
    ];

    protected $table = 'customers';
    protected $dates = ['created_at', 'updated_at', 'deleted_at'];


    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [

    ];

    /**
     * Category
     *
     * @return Relationship
     */
    public function category() {
        return $this->belongsTo(CustomerCategory::class, 'category_id');
    }

    /**
     * Primary Warehouse
     *
     * @return Relationship
     */
    public function main_warehouse() {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }


    /**
     * Warehouses
     *
     * @return Relationship
     */
    public function warehouses() {
        return $this->hasMany(Warehouse::class, 'customer_id');
    }

    public function scopeActive($query)
    {
        return $query->whereNull('deleted_at');
    }


}
