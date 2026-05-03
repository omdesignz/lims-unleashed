<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InventoryNeed extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'reference',
        'department_id',
        'lab_id',
        'requested_by_id',
        'approved_by_id',
        'inventory_order_id',
        'status',
        'needed_by_date',
        'justification',
        'approval_notes',
        'submitted_at',
        'approved_at',
        'rejected_at',
    ];

    protected function casts(): array
    {
        return [
            'needed_by_date' => 'date',
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
        ];
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function lab(): BelongsTo
    {
        return $this->belongsTo(VAPLab::class, 'lab_id');
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_id');
    }

    public function inventoryOrder(): BelongsTo
    {
        return $this->belongsTo(InventoryOrder::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(InventoryNeedItem::class);
    }
}
