<?php

namespace App\Models;

use App\Enums\InventoryCategoryType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class ItemCategory extends Model
{
    use HasFactory, SoftDeletes;

    public const MENU_NAME = 'item_categories';

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
        'inventory_type',
    ];

    protected $table = 'item_categories';

    protected $dates = ['created_at', 'updated_at', 'deleted_at'];

    protected $attributes = ['inventory_type' => 'material'];

    protected function casts(): array
    {
        return ['inventory_type' => InventoryCategoryType::class];
    }

    public function scopeWithTypeLock(Builder $query): Builder
    {
        return $query->addSelect($this->qualifyColumn('*'))->selectSub(
            DB::table('inventory_category_usage')->selectRaw('true')
                ->whereColumn('category_id', $this->qualifyColumn('id'))->limit(1), 'type_locked');
    }

    public function typeIsLocked(): bool
    {
        if (array_key_exists('type_locked', $this->getAttributes())) {
            return (bool) $this->getAttribute('type_locked');
        }

        return $this->exists && DB::table('inventory_category_usage')->where('category_id', $this->getKey())->exists();
    }

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
