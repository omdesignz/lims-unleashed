<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PersonnelQualification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'capability',
        'department_id',
        'qualified_by_id',
        'authorized_from',
        'authorized_until',
        'training_completed_at',
        'training_reference',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'authorized_from' => 'date',
        'authorized_until' => 'date',
        'training_completed_at' => 'date',
        'is_active' => 'boolean',
    ];

    public function monitoringStatus(): string
    {
        if (! $this->is_active) {
            return 'inactive';
        }

        if ($this->authorized_from && $this->authorized_from->isFuture()) {
            return 'scheduled';
        }

        if ($this->authorized_until && $this->authorized_until->isPast()) {
            return 'expired';
        }

        if ($this->authorized_until && $this->authorized_until->lte(now()->addDays(30))) {
            return 'expiring_critical';
        }

        if ($this->authorized_until && $this->authorized_until->lte(now()->addDays(60))) {
            return 'expiring_soon';
        }

        return 'active';
    }

    public function renewalReadiness(): string
    {
        if (blank($this->training_reference)) {
            return 'missing_evidence';
        }

        if (! $this->training_completed_at) {
            return 'training_pending';
        }

        if ($this->authorized_until && $this->authorized_until->lte(now()->addDays(60))) {
            return 'ready_for_review';
        }

        return 'on_track';
    }

    public function daysUntilExpiry(): ?int
    {
        if (! $this->authorized_until) {
            return null;
        }

        return now()->startOfDay()->diffInDays($this->authorized_until->startOfDay(), false);
    }

    public function followUpDueAt(): ?CarbonInterface
    {
        if ($this->authorized_until) {
            return $this->authorized_until->copy()->subDays(30);
        }

        if ($this->authorized_from) {
            return $this->authorized_from->copy()->addMonths(11);
        }

        return null;
    }

    public function followUpState(): string
    {
        $followUpDueAt = $this->followUpDueAt();

        if (! $followUpDueAt) {
            return 'unscheduled';
        }

        if ($followUpDueAt->isPast()) {
            return 'overdue';
        }

        if ($followUpDueAt->lte(now()->addDays(14))) {
            return 'due_soon';
        }

        return 'scheduled';
    }

    /**
     * @return array{
     *     id:int,
     *     capability:string,
     *     department:?array{id:int,name:string},
     *     qualified_by:?array{id:int,name:string},
     *     authorized_from:?string,
     *     authorized_until:?string,
     *     training_completed_at:?string,
     *     training_reference:?string,
     *     notes:?string,
     *     is_active:bool,
     *     monitoring_status:string,
     *     renewal_readiness:string,
     *     follow_up_state:string,
     *     follow_up_due_at:?string,
     *     days_until_expiry:?int
     * }
     */
    public function toMonitoringArray(): array
    {
        return [
            'id' => $this->id,
            'capability' => $this->capability,
            'department' => $this->department ? [
                'id' => $this->department->id,
                'name' => $this->department->name,
            ] : null,
            'qualified_by' => $this->qualifiedBy ? [
                'id' => $this->qualifiedBy->id,
                'name' => $this->qualifiedBy->name,
            ] : null,
            'authorized_from' => $this->authorized_from?->toDateString(),
            'authorized_until' => $this->authorized_until?->toDateString(),
            'training_completed_at' => $this->training_completed_at?->toDateString(),
            'training_reference' => $this->training_reference,
            'notes' => $this->notes,
            'is_active' => (bool) $this->is_active,
            'monitoring_status' => $this->monitoringStatus(),
            'renewal_readiness' => $this->renewalReadiness(),
            'follow_up_state' => $this->followUpState(),
            'follow_up_due_at' => $this->followUpDueAt()?->toDateString(),
            'days_until_expiry' => $this->daysUntilExpiry(),
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function qualifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'qualified_by_id');
    }
}
