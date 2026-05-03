<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VAPLab extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'labs';

    protected $fillable = [
        'lab_id',
        'tenant_id',
        'name',
        'room_no',
        'description',
        'contact',
        'extension',
        'code',
        'email',
        'supervisor_id',
        'technical_head_id',
        'department_id',
    ];

    protected $casts = [
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the parent lab if this is a sub-lab
     */
    public function parentLab(): BelongsTo
    {
        return $this->belongsTo(VAPLab::class, 'lab_id');
    }

    /**
     * Get sub-labs
     */
    public function subLabs()
    {
        return $this->hasMany(VAPLab::class, 'lab_id');
    }

    /**
     * Get the supervisor (user)
     */
    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }

    /**
     * Get the technical head (user)
     */
    public function technicalHead(): BelongsTo
    {
        return $this->belongsTo(User::class, 'technical_head_id');
    }

    /**
     * Get the department
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Get the tenant
     */
    // public function tenant(): BelongsTo
    // {
    //     return $this->belongsTo(Tenant::class) ?? null;
    // }

    /**
     * Scope for active labs (not deleted)
     */
    public function scopeActive($query)
    {
        return $query->whereNull('deleted_at');
    }

    /**
     * Scope for labs by tenant
     */
    // public function scopeByTenant($query, $tenantId = null)
    // {
    //     $tenantId = $tenantId ?? auth()->user()->tenant_id;
    //     return $query->where('tenant_id', $tenantId);
    // }

    /**
     * Get formatted contact information
     */
    public function getContactInfoAttribute(): string
    {
        $info = [];
        if ($this->contact) {
            $info[] = "Contacto: {$this->contact}";
        }
        if ($this->extension) {
            $info[] = "Ext: {$this->extension}";
        }
        if ($this->email) {
            $info[] = "Email: {$this->email}";
        }
        
        return implode(' | ', $info);
    }
}