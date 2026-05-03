<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VAPNonConformityAction extends Model
{
    use HasFactory;

    protected $table = 'v_non_conformity_actions';

    protected $fillable = [
        'lab_id',
        'department_id',
        'nc_id',
        'correction',
        'corrective_action',
        'assigned_to_id',
        'approved_at',
        'due_at',
        'was_effective',
        'evidence'
    ];

    protected $casts = [
        'due_at' => 'datetime',
        'approved_at' => 'datetime',
        'was_effective' => 'boolean'
    ];

    // Relationships
    public function nonConformity(): BelongsTo
    {
        return $this->belongsTo(VAPNonConformity::class, 'nc_id');
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_id');
    }

    public function lab(): BelongsTo
    {
        return $this->belongsTo(VAPLab::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->whereNull('approved_at');
    }

    public function scopeOverdue($query)
    {
        return $query->whereNotNull('due_at')
            ->where('due_at', '<', now())
            ->whereNull('approved_at');
    }

    public function scopeEffective($query)
    {
        return $query->where('was_effective', true);
    }
}