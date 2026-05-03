<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class QualityCertificateRevision extends Model
{
    use SoftDeletes, LogsActivity;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'quality_certificate_revisions';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'quality_certificate_id',
        'revision_number',
        'version',
        'change_reason',
        'change_type',
        'change_description',
        'created_by_id',
        'approved_by_id',
        'is_current',
        'effective_date',
        'superseded_date',
        'snapshot_data',
        'activity_log_ids',
        'compliance_metadata',
        'revisable_type',
        'revisable_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_current' => 'boolean',
        'effective_date' => 'datetime',
        'superseded_date' => 'datetime',
        'snapshot_data' => 'array',
        'activity_log_ids' => 'array',
        'compliance_metadata' => 'array',
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'effective_date',
        'superseded_date',
        'deleted_at',
        'created_at',
        'updated_at',
    ];

    /**
     * Configure activity logging for ISO 17025 compliance
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['version', 'change_type', 'change_reason', 'is_current'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn(string $eventName) => $this->getDescriptionForEvent($eventName))
            ->useLogName('iso-revision')
            ->dontSubmitEmptyLogs();
    }

    /**
     * Get description for activity log
     */
    protected function getDescriptionForEvent(string $eventName): string
    {
        switch ($eventName) {
            case 'created':
                return "Created revision v{$this->version} for certificate #{$this->quality_certificate_id}";
            case 'updated':
                return "Updated revision v{$this->version}";
            case 'deleted':
                return "Deleted revision v{$this->version}";
            default:
                return "Performed {$eventName} on revision v{$this->version}";
        }
    }

    /**
     * Relationship: The quality certificate this revision belongs to
     */
    public function qualityCertificate(): BelongsTo
    {
        return $this->belongsTo(QualityCertificate::class);
    }

    /**
     * Relationship: User who created this revision
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    /**
     * Relationship: User who approved this revision
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_id');
    }

    /**
     * Relationship: Polymorphic relation for revisable entities
     * (allows the revision system to work with other models if needed)
     */
    public function revisable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Scope: Get only current revisions
     */
    public function scopeCurrent($query)
    {
        return $query->where('is_current', true);
    }

    /**
     * Scope: Get only superseded (historical) revisions
     */
    public function scopeHistorical($query)
    {
        return $query->where('is_current', false);
    }

    /**
     * Scope: Get revisions by change type
     */
    public function scopeByChangeType($query, $type)
    {
        return $query->where('change_type', $type);
    }

    /**
     * Scope: Get revisions within a date range
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('effective_date', [$startDate, $endDate]);
    }

    /**
     * Scope: Get revisions for a specific ISO section
     */
    public function scopeByIsoSection($query, $section)
    {
        return $query->where('compliance_metadata->iso_section', $section);
    }

    /**
     * Get the certificate data from snapshot
     */
    public function getCertificateDataAttribute(): array
    {
        return $this->snapshot_data['certificate'] ?? [];
    }

    /**
     * Get related collection product data from snapshot
     */
    public function getCollectionProductDataAttribute(): ?array
    {
        return $this->snapshot_data['relations']['collection_product'] ?? null;
    }

    /**
     * Get results data from snapshot
     */
    public function getResultsDataAttribute(): array
    {
        return $this->snapshot_data['relations']['results'] ?? [];
    }

    /**
     * Get customer data from snapshot
     */
    public function getCustomerDataAttribute(): ?array
    {
        return $this->snapshot_data['relations']['customer'] ?? null;
    }

    /**
     * Get product data from snapshot
     */
    public function getProductDataAttribute(): ?array
    {
        return $this->snapshot_data['relations']['product'] ?? null;
    }

    /**
     * Get metadata from snapshot
     */
    public function getSnapshotMetadataAttribute(): array
    {
        return $this->snapshot_data['metadata'] ?? [];
    }

    /**
     * Get ISO compliance metadata
     */
    public function getIsoSectionAttribute(): ?string
    {
        return $this->compliance_metadata['iso_section'] ?? null;
    }

    /**
     * Get change category
     */
    public function getChangeCategoryAttribute(): ?string
    {
        return $this->compliance_metadata['change_category'] ?? null;
    }

    /**
     * Get risk assessment level
     */
    public function getRiskAssessmentAttribute(): ?string
    {
        return $this->compliance_metadata['risk_assessment'] ?? null;
    }

    /**
     * Check if revision requires review
     */
    public function getRequiresReviewAttribute(): bool
    {
        return $this->compliance_metadata['review_required'] ?? false;
    }

    /**
     * Get approval workflow type
     */
    public function getApprovalWorkflowAttribute(): ?string
    {
        return $this->compliance_metadata['approval_workflow'] ?? null;
    }

    /**
     * Get related activity logs
     */
    public function relatedActivityLogs()
    {
        if (empty($this->activity_log_ids)) {
            return collect();
        }

        return IsoActivityLog::whereIn('id', $this->activity_log_ids)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Mark this revision as superseded
     */
    public function markAsSuperseded(): bool
    {
        return $this->update([
            'is_current' => false,
            'superseded_date' => now(),
        ]);
    }

    /**
     * Mark this revision as current
     */
    public function markAsCurrent(): bool
    {
        return $this->update([
            'is_current' => true,
            'superseded_date' => null,
        ]);
    }

    /**
     * Get a specific field value from the certificate snapshot
     */
    public function getCertificateField(string $field, $default = null)
    {
        return $this->snapshot_data['certificate'][$field] ?? $default;
    }

    /**
     * Get the formatted change description
     */
    public function getFormattedChangeDescriptionAttribute(): string
    {
        if ($this->change_description) {
            return $this->change_description;
        }

        // Generate from activity logs if available
        $logs = $this->relatedActivityLogs();
        
        if ($logs->isNotEmpty()) {
            $changes = [];
            foreach ($logs as $log) {
                if ($log->properties && isset($log->properties['attributes'])) {
                    foreach ($log->properties['attributes'] as $key => $value) {
                        $oldValue = $log->properties['old'][$key] ?? null;
                        if ($oldValue != $value) {
                            $changes[] = $this->formatFieldChange($key, $oldValue, $value);
                        }
                    }
                }
            }
            
            if (!empty($changes)) {
                return implode('; ', $changes);
            }
        }

        return 'No specific changes documented';
    }

    /**
     * Format a field change for display
     */
    private function formatFieldChange(string $field, $oldValue, $newValue): string
    {
        $fieldLabels = [
            'status' => 'Status',
            'obs' => 'Observations',
            'validated_by' => 'Validated By',
            'validated_at' => 'Validation Date',
            // Add more field labels as needed
        ];

        $label = $fieldLabels[$field] ?? ucwords(str_replace('_', ' ', $field));
        
        $formattedOld = $this->formatValue($oldValue);
        $formattedNew = $this->formatValue($newValue);
        
        return "{$label}: {$formattedOld} → {$formattedNew}";
    }

    /**
     * Format a value for display
     */
    private function formatValue($value): string
    {
        if (is_null($value)) {
            return '[empty]';
        }
        
        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }
        
        if (is_array($value) || is_object($value)) {
            return '[data]';
        }
        
        return (string) $value;
    }

    /**
     * Check if this revision can be restored
     */
    public function canBeRestored(): bool
    {
        // Check various conditions for restoration
        return !$this->is_current 
            && !$this->trashed()
            && $this->qualityCertificate->exists
            && $this->snapshot_data !== null;
    }

    /**
     * Get restoration validation rules
     */
    public static function getRestorationValidationRules(): array
    {
        return [
            'restore_reason' => 'required|string|min:20|max:1000',
            'iso_section' => 'required|string',
            'approved_by_id' => 'required|exists:users,id',
            'change_category' => 'required|in:CORRECTION,REISSUE,EMERGENCY,REGULATORY',
            'confirmed' => 'required|accepted',
        ];
    }

    /**
     * Create a new revision from the current state of a certificate
     */
    public static function createFromCertificate(
        QualityCertificate $certificate, 
        string $changeReason, 
        string $changeType = 'UPDATED',
        ?User $createdBy = null,
        ?User $approvedBy = null,
        array $complianceMetadata = []
    ): self {
        $createdBy = $createdBy ?? auth()->user();
        
        // Calculate next revision number
        $lastRevision = self::where('quality_certificate_id', $certificate->id)
            ->orderBy('revision_number', 'desc')
            ->first();
        
        $revisionNumber = $lastRevision ? $lastRevision->revision_number + 1 : 1;
        $version = self::calculateVersion($revisionNumber);
        
        // Create snapshot data
        $snapshot = $certificate->createIsoSnapshot();
        
        // Get recent activity logs
        $recentLogs = activity()
            ->performedOn($certificate)
            ->where('created_at', '>=', now()->subMinutes(10))
            ->get()
            ->pluck('id')
            ->toArray();
        
        // Create the revision
        $revision = self::create([
            'quality_certificate_id' => $certificate->id,
            'revision_number' => $revisionNumber,
            'version' => $version,
            'change_reason' => $changeReason,
            'change_type' => $changeType,
            'change_description' => $certificate->generateIsoChangeDescription(),
            'created_by_id' => $createdBy->id,
            'approved_by_id' => $approvedBy?->id,
            'is_current' => true,
            'effective_date' => now(),
            'snapshot_data' => $snapshot,
            'activity_log_ids' => $recentLogs,
            'compliance_metadata' => array_merge([
                'iso_section' => $complianceMetadata['iso_section'] ?? '8.9.1',
                'change_category' => $complianceMetadata['change_category'] ?? 'ROUTINE',
                'risk_assessment' => $complianceMetadata['risk_assessment'] ?? 'LOW',
                'review_required' => $complianceMetadata['review_required'] ?? false,
                'approval_workflow' => $complianceMetadata['approval_workflow'] ?? 'DIRECT',
            ], $complianceMetadata),
        ]);
        
        // Mark previous revisions as superseded
        self::where('quality_certificate_id', $certificate->id)
            ->where('id', '!=', $revision->id)
            ->where('is_current', true)
            ->update(['is_current' => false, 'superseded_date' => now()]);
        
        // Log the revision creation
        activity()
            ->performedOn($revision)
            ->causedBy($createdBy)
            ->withProperties([
                'revision_number' => $revisionNumber,
                'version' => $version,
                'change_reason' => $changeReason,
                'change_type' => $changeType,
            ])
            ->log('ISO_REVISION_CREATED');
        
        return $revision;
    }

    /**
     * Calculate version number from revision number
     */
    public static function calculateVersion(int $revisionNumber): string
    {
        $major = floor(($revisionNumber - 1) / 10) + 1;
        $minor = ($revisionNumber - 1) % 10;
        return sprintf('%d.%d', $major, $minor);
    }

    /**
     * Get all change types with their descriptions
     */
    public static function getChangeTypes(): array
    {
        return [
            'CREATED' => 'Initial creation of certificate',
            'UPDATED' => 'Regular update or modification',
            'CORRECTED' => 'Correction of errors or mistakes',
            'REISSUED' => 'Re-issue of certificate',
            'WITHDRAWN' => 'Withdrawal or cancellation',
        ];
    }

    /**
     * Get change type description
     */
    public function getChangeTypeDescriptionAttribute(): string
    {
        $descriptions = self::getChangeTypes();
        return $descriptions[$this->change_type] ?? 'Unknown change type';
    }

    /**
     * Get the breadcrumb trail for this revision
     */
    public function getBreadcrumbTrail(): array
    {
        return [
            ['label' => 'Quality Certificates', 'url' => route('qualitycertificates.index')],
            ['label' => $this->qualityCertificate->code, 'url' => route('qualitycertificates.show', $this->qualityCertificate)],
            ['label' => 'Revisions', 'url' => route('qualitycertificates.iso-revisions.index', $this->qualityCertificate)],
            ['label' => 'v' . $this->version, 'url' => route('qualitycertificates.iso-revisions.show', [$this->qualityCertificate, $this])],
        ];
    }

    /**
     * Boot method for model events
     */
    protected static function boot()
    {
        parent::boot();

        // Ensure only one current revision per certificate
        static::saving(function ($model) {
            if ($model->is_current) {
                self::where('quality_certificate_id', $model->quality_certificate_id)
                    ->where('id', '!=', $model->id)
                    ->where('is_current', true)
                    ->update(['is_current' => false, 'superseded_date' => now()]);
            }
        });

        // When deleting a revision, ensure there's always a current revision
        static::deleting(function ($model) {
            if ($model->is_current) {
                $newCurrent = self::where('quality_certificate_id', $model->quality_certificate_id)
                    ->where('id', '!=', $model->id)
                    ->orderBy('revision_number', 'desc')
                    ->first();
                
                if ($newCurrent) {
                    $newCurrent->update(['is_current' => true, 'superseded_date' => null]);
                }
            }
        });
    }
}