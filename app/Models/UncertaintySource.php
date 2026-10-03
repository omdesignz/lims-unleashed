<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use LogicException;

class UncertaintySource extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'lab_id',
        'title',
        'source_type',
        'department_id',
        'parameter_id',
        'inventory_item_id',
        'description',
        'estimation_method',
        'control_strategy',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'lab_id' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (self $source): void {
            if ($source->isDirty('lab_id')) {
                throw new LogicException('An uncertainty source cannot change its owning laboratory.');
            }
        });
    }

    public function lab(): BelongsTo
    {
        return $this->belongsTo(VAPLab::class, 'lab_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function parameter(): BelongsTo
    {
        return $this->belongsTo(Parameter::class);
    }

    public function inventoryItem(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }
}
