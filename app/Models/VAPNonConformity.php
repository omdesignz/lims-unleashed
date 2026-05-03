<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VAPNonConformity extends Model
{
    use HasFactory;

    protected $table = 'v_non_conformities';

    protected $fillable = [
        'lab_id',
        'department_id',
        'nc_number',
        'title',
        'description',
        'status',
        'severity',
        'category',
        'sample_id',
        'test_method',
        'equipment_id',
        'batch_number',
        'reported_by',
        'reported_by_id',
        'assigned_to',
        'assigned_to_id',
        'reported_at',
        'due_date',
        'resolved_at',
        'verified_at',
        'occurrence_area',
        'approved_by',
        'approved_by_id',
        'approved_at',
        'was_effective',
        'effective_at',
        'evidence',
        'root_cause',
        'corrective_actions',
        'preventive_actions',
        'comments',
        'attachments'
    ];

    protected $casts = [
        'reported_at' => 'datetime',
        'due_date' => 'datetime',
        'resolved_at' => 'datetime',
        'verified_at' => 'datetime',
        'approved_at' => 'datetime',
        'effective_at' => 'datetime',
        'was_effective' => 'boolean',
        'attachments' => 'array'
    ];

    // Relationships
    public function lab(): BelongsTo
    {
        return $this->belongsTo(VAPLab::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function actions(): HasMany
    {
        return $this->hasMany(VAPNonConformityAction::class, 'nc_id');
    }

    public function reportedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by_id');
    }

    public function assignedToUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_id');
    }

    // Scopes
    public function scopeOpen($query)
    {
        return $query->whereIn('status', ['opened', 'in_progress']);
    }

    public function scopeClosed($query)
    {
        return $query->where('status', 'closed');
    }

    public function scopeCritical($query)
    {
        return $query->where('severity', 'critical');
    }

    public function scopeByLab($query, $labId)
    {
        return $query->where('lab_id', $labId);
    }

    public function scopeByDepartment($query, $departmentId)
    {
        return $query->where('department_id', $departmentId);
    }

    // Methods
    public function isOverdue(): bool
    {
        if (!$this->due_date) {
            return false;
        }

        return $this->status !== 'closed' && $this->due_date->isPast();
    }

    public function daysOpen(): int
    {
        if (!$this->reported_at) {
            return 0;
        }

        return $this->reported_at->diffInDays(now());
    }

    public function generateNcNumber(): string
    {
        $prefix = 'NC';
        $year = now()->format('Y');
        $month = now()->format('m');
        $count = self::whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->count() + 1;

        return sprintf('%s-%s%s-%04d', $prefix, $year, $month, $count);
    }
}