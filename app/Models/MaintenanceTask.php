<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use HighSolutions\EloquentSequence\Sequence;

class MaintenanceTask extends Model
{
    use HasFactory, SoftDeletes, Sequence;

    public const MENU_NAME = 'maintenance_tasks';

    protected $fillable = [
        'maintenance_task_no',
        'maintenance_task_year',
        'seq',
        'name',
        'description',
        'equipment_id',
        'category_id',
        'due_date',
        'is_executed',
        'executed_by_supplier',
        'supplier_id',
        'obs',
        'cost',
        'is_planned',
        'periodicity',
        'periodicity_unit',
        'previous_date',
        'next_date',
        'acceptance_criteria',
        'result',
        'range',
        'calibration_points',
        'calibration_status',
        'calibration_certificate_no'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'due_date' => 'date',
        'previous_date' => 'date',
        'next_date' => 'date',
        'executed_by_supplier' => 'boolean',
        'is_planned' => 'boolean',
        'is_executed' => 'boolean',
        'cost' => 'decimal:2',
        'deleted_at' => 'datetime',
    ];

    public function sequence()
    {
        return [
            'group' => ['maintenance_task_year', 'category_id'],
            'fieldName' => 'seq',
            'notUpdateOnDelete' => true,
        ];
    }

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'equipment_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(MaintenanceCategory::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(InventoryItemSupplier::class);
    }

    public static function boot()
    {
        parent::boot();

        static::creating(function ($task) {
            $task->maintenance_task_no = MaintenanceCategory::findOrFail($task->category_id)->code . ' ' . $task->maintenance_task_year . '/' . str_pad($task->seq, 3, '0', STR_PAD_LEFT);
        });
    }

}