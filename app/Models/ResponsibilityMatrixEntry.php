<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ResponsibilityMatrixEntry extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'department_id',
        'lab_id',
        'process_area',
        'activity',
        'responsible_user_id',
        'accountable_user_id',
        'consulted_roles',
        'informed_roles',
        'evidence_requirement',
        'is_active',
        'effective_from',
        'effective_until',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'effective_from' => 'date',
            'effective_until' => 'date',
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

    public function responsibleUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_user_id');
    }

    public function accountableUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'accountable_user_id');
    }
}
