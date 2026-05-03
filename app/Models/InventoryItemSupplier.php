<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;


class InventoryItemSupplier extends Model
{
    use HasFactory, SoftDeletes;

    public CONST MENU_NAME = 'isuppliers';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'address',
        'currency',
    ];

    protected $table = 'i_suppliers';
    protected $dates = ['created_at', 'updated_at', 'deleted_at'];

    public function scopeActive($query)
    {
        return $query->whereNull('deleted_at');
    }

    public function assessments(): HasMany
    {
        return $this->hasMany(InventorySupplierAssessment::class, 'inventory_item_supplier_id');
    }

}
