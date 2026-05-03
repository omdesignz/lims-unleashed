<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use HighSolutions\EloquentSequence\Sequence;
use Illuminate\Database\Eloquent\SoftDeletes;

class Occurrence extends Model
{
    use HasFactory, Sequence, SoftDeletes;

    public const MENU_NAME = 'occurrences';

    protected $fillable = [
        'occurrence_no',
        'occurrence_year',
        'seq',
        'date_reported',
        'issue_description',
        'corrective_action',
        'date_resolved',
        'notification_date',
        'client_process_open_notification_date',
        'analysis',
        'has_risk_correction_budget',
        'reason_for_no_risk_correction_budget',
        'has_non_conformity_terms',
        'effect_corrective_actions',
        'cause_corrective_actions',
        'implementation_date',
        'update_risk_matrix',
        'client_process_close_notification_date',
        'client_acceptance',
        'client_acceptance_comments',
        'date_closed',
        'obs',
        'was_effective',
        'status_id',
        'responsible_name',
        'department_id',
        'user_id',
        'origin_id',
        'category_id',
    ];

    protected $casts = [
        'date_reported' => 'date',
        'date_resolved' => 'date',
        'notification_date' => 'date',
        'client_process_open_notification_date' => 'date',
        'has_risk_correction_budget' => 'boolean',
        'has_non_conformity_terms' => 'boolean',
        'implementation_date' => 'date',
        'update_risk_matrix' => 'boolean',
        'client_process_close_notification_date' => 'date',
        'client_acceptance' => 'boolean',
        'date_closed' => 'date',
        'was_effective' => 'boolean',
    ];

    public function sequence()
    {
        return [
            'group' => ['occurrence_year'],
            'fieldName' => 'seq',
            'notUpdateOnDelete' => true,
        ];
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function origin(): BelongsTo
    {
        return $this->belongsTo(OccurrenceOrigin::class);
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(OccurrenceStatus::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(OccurrenceCategory::class);
    }

    public static function boot()
    {
        parent::boot();

        static::creating(function ($occurrence) {
            $occurrence->occurrence_no = $occurrence->occurrence_year . '/' . str_pad($occurrence->seq, 3, '0', STR_PAD_LEFT);
        });
    }
}
