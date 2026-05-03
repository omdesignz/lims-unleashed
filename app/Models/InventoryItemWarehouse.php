<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class InventoryItemWarehouse extends Model
{
    use HasFactory, SoftDeletes;

    public CONST MENU_NAME = 'iwarehouses';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'location_id',
        'is_refrigerated',
        'is_ventilated',
        'has_air_exhaustion',
    ];

    protected $table = 'i_warehouses';
    protected $dates = ['created_at', 'updated_at', 'deleted_at'];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'has_air_exhaustion' => 'boolean',
        'is_ventilated' => 'boolean',
        'is_refrigerated' => 'boolean',
    ];

    /**
     * Item Location
     *
     * @return Relationship
     */
    public function location() {
        return $this->belongsTo(InventoryItemLocation::class, 'location_id');
    }

    public function scopeActive($query)
    {
        return $query->whereNull('deleted_at');
    }

}
