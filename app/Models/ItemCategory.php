<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class ItemCategory extends Model
{
    use HasFactory, SoftDeletes;

    public CONST MENU_NAME = 'item_categories';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'description',
        'code',
        'parent_id',
    ];

    protected $table = 'item_categories';
    protected $dates = ['created_at', 'updated_at', 'deleted_at'];

    /**
     * Parent
     *
     * @return Relationship
     */
    public function parent()
    {
        return $this->belongsTo(ItemCategory::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(ItemCategory::class, 'parent_id');
    }

    public function items()
    {
        return $this->hasMany(InventoryItem::class, 'category_id');
    }

    public function scopeRoot($query)
    {
        return $query->whereNull('parent_id');
    }

    public function scopeActive($query)
    {
        return $query->whereNull('deleted_at');
    }

    public function getFullPathAttribute()
    {
        $path = [];
        $category = $this;
        
        while ($category) {
            array_unshift($path, $category->name);
            $category = $category->parent;
        }
        
        return implode(' → ', $path);
    }

}