<?php

namespace App\Traits;

use App\Models\QualityCertificateRevision;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait ISO17025Revisionable
{
    use LogsActivity;

    protected $isoChangeReason = null;
    protected $isoApprovalData = null;
    
    /**
     * Configure activity logging
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly($this->getLoggableAttributes())
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => $this->getIsoDescription($eventName))
            ->useLogName($this->getLogName())
            ->dontLogIfAttributesChangedOnly(['updated_at'])
            ->submitEmptyLogs(false);
    }

    /**
     * Custom method to log ISO 17025 compliant changes
     */
   public function logIsoChange(array $attributes, string $reason, array $approvalData = null): ?QualityCertificateRevision
    {
        $this->isoChangeReason = $reason;
        $this->isoApprovalData = $approvalData;
        
        // Update the model
        $this->update($attributes);
        
        // If this is a quality certificate, create revision
        if ($this instanceof \App\Models\QualityCertificate) {
            return $this->createIsoRevision($reason, $approvalData);
        }
        
        return null;
    }

    /**
     * Create ISO 17025 compliant revision
     */
    protected function createIsoRevision(string $reason, ?array $approvalData = null): QualityCertificateRevision
    {
        return DB::transaction(function () use ($reason, $approvalData) {
            // Get latest activity logs for this certificate
            $recentLogs = \Spatie\Activitylog\Models\Activity::where('subject_type', get_class($this))
                ->where('subject_id', $this->id)
                ->where('created_at', '>=', now()->subMinutes(5))
                ->orderBy('created_at', 'desc')
                ->get()
                ->pluck('id')
                ->toArray();

            // Mark previous revision as superseded
            QualityCertificateRevision::where('quality_certificate_id', $this->id)
                ->where('is_current', true)
                ->update([
                    'is_current' => false,
                    'superseded_date' => now()
                ]);

            // Calculate next revision
            $lastRevision = QualityCertificateRevision::where('quality_certificate_id', $this->id)
                ->orderBy('revision_number', 'desc')
                ->first();

            $revisionNumber = $lastRevision ? $lastRevision->revision_number + 1 : 1;
            $version = $this->calculateIsoVersion($revisionNumber);

            // Create comprehensive snapshot
            $snapshot = $this->createIsoSnapshot();

            // Create revision record
            $revision = QualityCertificateRevision::create([
                'quality_certificate_id' => $this->id,
                'revision_number' => $revisionNumber,
                'version' => $version,
                'change_reason' => $reason,
                'change_type' => $this->determineIsoChangeType(),
                'change_description' => $this->generateIsoChangeDescription(),
                'created_by_id' => Auth::id(),
                'approved_by_id' => $approvalData['approved_by_id'] ?? Auth::id(),
                'is_current' => true,
                'effective_date' => now(),
                'snapshot_data' => $snapshot,
                'activity_log_ids' => $recentLogs,
                'compliance_metadata' => [
                    'iso_section' => $approvalData['iso_section'] ?? '8.9.1',
                    'change_category' => $approvalData['change_category'] ?? 'ROUTINE',
                    'risk_assessment' => $approvalData['risk_assessment'] ?? 'LOW',
                    'review_required' => $approvalData['review_required'] ?? false,
                    'approval_workflow' => $approvalData['approval_workflow'] ?? 'DIRECT',
                ],
            ]);

            // Log the revision creation
            activity()
                ->performedOn($revision)
                ->causedBy(Auth::user())
                ->withProperties([
                    'revision_number' => $revisionNumber,
                    'version' => $version,
                    'change_reason' => $reason,
                    'snapshot_keys' => array_keys($snapshot),
                ])
                ->log('REVISION_CREATED');

            return $revision;
        });
    }

    /**
     * Create ISO-compliant snapshot
     */
     protected function createIsoSnapshot(): array
    {
        $snapshot = [
            'certificate' => $this->fresh()->toArray(),
            'relations' => [],
            'metadata' => [
                'snapshot_at' => now()->toISOString(),
                'created_by' => Auth::user() ? Auth::user()->only(['id', 'name', 'email']) : null,
                'system_version' => config('app.version'),
                'iso_standard' => 'ISO/IEC 17025:2017',
            ]
        ];

        // Load and snapshot relations based on model type
        if ($this instanceof \App\Models\QualityCertificate) {
            $snapshot['relations'] = [
                'collection_product' => $this->collectionProduct ? 
                    $this->collectionProduct->toArray() : null,
                'results' => $this->collectionProduct ? 
                    $this->collectionProduct->results->map->toArray()->toArray() : [],
                'customer' => $this->customer ? $this->customer->toArray() : null,
                'product' => $this->product ? $this->product->toArray() : null,
                'warehouse' => $this->warehouse ? $this->warehouse->toArray() : null,
            ];
        } elseif ($this instanceof \App\Models\CollectionProduct) {
            $snapshot['relations'] = [
                'results' => $this->results->map->toArray()->toArray(),
                'product' => $this->product ? $this->product->toArray() : null,
                'customer' => $this->customer ? $this->customer->toArray() : null,
            ];
        }

        return $snapshot;
    }

    /**
     * Generate ISO-compliant change description
     */
   protected function generateIsoChangeDescription(): string
    {
        $changes = [];
        
        // Get activity logs for context
        $lastActivity = \Spatie\Activitylog\Models\Activity::where('subject_type', get_class($this))
            ->where('subject_id', $this->id)
            ->latest()
            ->first();

        if ($lastActivity) {
            $properties = $lastActivity->properties;
            
            if ($properties && isset($properties['attributes']) && isset($properties['old'])) {
                foreach ($properties['attributes'] as $key => $newValue) {
                    $oldValue = $properties['old'][$key] ?? null;
                    if ($oldValue != $newValue) {
                        $changes[] = sprintf(
                            '%s: "%s" → "%s"',
                            $this->getAttributeLabel($key),
                            $this->formatIsoValue($oldValue),
                            $this->formatIsoValue($newValue)
                        );
                    }
                }
            }
        }

        return implode('; ', $changes) ?: 'No detectable field changes';
    }

    /**
     * Get related revisions for certificate
     */
    public function isoRevisions(): MorphMany
    {
        return $this->morphMany(QualityCertificateRevision::class, 'revisable');
    }

    /**
     * Helper methods
     */
    protected function calculateIsoVersion(int $revision): string
    {
        $major = floor(($revision - 1) / 10) + 1;
        $minor = ($revision - 1) % 10;
        return sprintf('%d.%d', $major, $minor);
    }

    protected function determineIsoChangeType(): string
    {
        $dirty = $this->getDirty();
        
        if ($this->wasRecentlyCreated) {
            return 'CREATED';
        }

        if (isset($dirty['status'])) {
            if ($dirty['status'] == 2) return 'WITHDRAWN';
            if ($dirty['status'] == 3) return 'REISSUED';
        }

        if (isset($dirty['obs']) && preg_match('/\b(correction|error|mistake)\b/i', $dirty['obs'])) {
            return 'CORRECTED';
        }

        return 'UPDATED';
    }

    protected function getIsoDescription(string $eventName): string
    {
        $descriptions = [
            'created' => 'Created new record',
            'updated' => 'Updated record' . ($this->isoChangeReason ? ": {$this->isoChangeReason}" : ''),
            'deleted' => 'Deleted record',
            'restored' => 'Restored record',
        ];

        return $descriptions[$eventName] ?? "{$eventName} record";
    }

    protected function getLogName(): string
    {
        return class_basename($this) . 'Log';
    }

    protected function getLoggableAttributes(): array
    {
        // Define which attributes to log based on model
        $loggable = [
            'status',
            'obs',
            'validated_by',
            'validated_at',
            'extra_data',
        ];

        // Add model-specific attributes
        if ($this instanceof \App\Models\QualityCertificate) {
            $loggable = array_merge($loggable, [
                'file_path',
                'validated_by_id',
                'validated_on_behalf_of_id',
            ]);
        } elseif ($this instanceof \App\Models\Result) {
            $loggable = array_merge($loggable, [
                'inserted_value',
                'verified_value',
                'approved_value',
                'insertion_notes',
                'verification_notes',
                'approval_notes',
            ]);
        }

        return $loggable;
    }

    protected function formatIsoValue($value): string
    {
        if (is_null($value)) return 'NULL';
        if (is_bool($value)) return $value ? 'TRUE' : 'FALSE';
        if (is_array($value) || is_object($value)) return json_encode($value);
        
        return (string) $value;
    }

    protected function getAttributeLabel(string $key): string
    {
        $labels = [
            'status' => 'Status',
            'obs' => 'Observations',
            'validated_by' => 'Validated By',
            // Add more labels as needed
        ];

        return $labels[$key] ?? ucwords(str_replace('_', ' ', $key));
    }
}