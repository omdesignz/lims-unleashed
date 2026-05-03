<?php

namespace App\Traits;

use App\Models\QualityCertificateRevision;
use App\Models\SystemAuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

trait Revisionable
{
    protected static function bootRevisionable()
    {
        static::updating(function ($model) {
            if ($model->shouldCreateRevision()) {
                $model->createRevision();
            }
            
            if ($model->isQualityCertificate()) {
                $model->logAuditTrail('UPDATE');
            }
        });

        static::created(function ($model) {
            if ($model->isQualityCertificate()) {
                $model->logAuditTrail('CREATE');
            }
        });

        static::deleted(function ($model) {
            if ($model->isQualityCertificate()) {
                $model->logAuditTrail('DELETE');
            }
        });
    }

    protected function shouldCreateRevision(): bool
    {
        // Only create revisions for quality certificates
        if (!$this->isQualityCertificate()) {
            return false;
        }

        // Check if any important fields changed
        $importantFields = [
            'validated_by', 'status', 'obs', 'file_path',
            'validated_at', 'validated_by_id', 'extra_data'
        ];

        foreach ($importantFields as $field) {
            if ($this->isDirty($field)) {
                return true;
            }
        }

        return false;
    }

    protected function createRevision(): void
    {
        DB::transaction(function () {
            // Mark previous revisions as not current
            QualityCertificateRevision::where('quality_certificate_id', $this->id)
                ->where('is_current', true)
                ->update(['is_current' => false, 'superseded_date' => now()]);

            // Get the next revision number
            $lastRevision = QualityCertificateRevision::where('quality_certificate_id', $this->id)
                ->orderBy('revision_number', 'desc')
                ->first();

            $revisionNumber = $lastRevision ? $lastRevision->revision_number + 1 : 1;
            $version = $this->calculateVersion($revisionNumber);

            // Create comprehensive snapshot
            $snapshot = $this->createSnapshot();

            // Create new revision
            QualityCertificateRevision::create([
                'quality_certificate_id' => $this->id,
                'revision_number' => $revisionNumber,
                'version' => $version,
                'change_type' => $this->determineChangeType(),
                'change_reason' => request()->input('change_reason'),
                'change_description' => $this->generateChangeDescription(),
                'created_by_id' => Auth::id(),
                'approved_by_id' => Auth::id(), // Could be separate approval
                'is_current' => true,
                'effective_date' => now(),
                'snapshot_data' => $snapshot,
            ]);
        });
    }

    protected function createSnapshot(): array
    {
        // Main certificate data
        $snapshot = [
            'certificate' => $this->fresh()->toArray(),
            'collection_product' => null,
            'results' => [],
            'related_data' => [],
            'metadata' => [
                'snapshot_at' => now()->toISOString(),
                'user_id' => Auth::id(),
                'user_name' => Auth::user()->name ?? null,
            ]
        ];

        // Get related collection product
        if ($this->collection_id) {
            $collectionProduct = DB::table('collection_product')
                ->where('id', $this->collection_id)
                ->first();
            
            if ($collectionProduct) {
                $snapshot['collection_product'] = (array) $collectionProduct;
                
                // Get all results for this collection
                $results = DB::table('results')
                    ->where('collection_id', $this->collection_id)
                    ->get()
                    ->map(fn($result) => (array) $result)
                    ->toArray();
                
                $snapshot['results'] = $results;
            }
        }

        // Get other related data
        $snapshot['related_data'] = [
            'warehouse' => $this->warehouse ? $this->warehouse->toArray() : null,
            'customer' => $this->customer ? $this->customer->toArray() : null,
            'product' => $this->product ? $this->product->toArray() : null,
            'invoice' => $this->invoice ? $this->invoice->toArray() : null,
        ];

        return $snapshot;
    }

    protected function logAuditTrail(string $action): void
    {
        $oldValues = $this->getOriginal();
        $newValues = $this->getAttributes();

        // Remove sensitive data if needed
        unset($oldValues['password'], $newValues['password']);

        SystemAuditLog::create([
            'user_id' => Auth::id(),
            'action' => $action,
            'entity_type' => get_class($this),
            'entity_id' => $this->id,
            'quality_certificate_id' => $this->isQualityCertificate() ? $this->id : null,
            'old_values' => $action === 'CREATE' ? null : $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'url' => request()->fullUrl(),
            'remarks' => request()->input('audit_remarks'),
            'created_at' => now(),
        ]);
    }

    protected function isQualityCertificate(): bool
    {
        return get_class($this) === \App\Models\QualityCertificate::class;
    }

    protected function determineChangeType(): string
    {
        if ($this->wasRecentlyCreated) {
            return 'CREATED';
        }

        $dirtyFields = $this->getDirty();
        
        if (isset($dirtyFields['status']) && $dirtyFields['status'] == 2) {
            return 'WITHDRAWN';
        }

        if (isset($dirtyFields['obs']) && str_contains($dirtyFields['obs'], 'correction')) {
            return 'CORRECTED';
        }

        return 'UPDATED';
    }

    protected function calculateVersion(int $revisionNumber): string
    {
        $major = ceil($revisionNumber / 10);
        $minor = $revisionNumber % 10;
        return "{$major}.{$minor}";
    }

    protected function generateChangeDescription(): string
    {
        $changes = [];
        $dirty = $this->getDirty();

        foreach ($dirty as $field => $newValue) {
            $oldValue = $this->getOriginal($field);
            $changes[] = "{$field}: {$oldValue} → {$newValue}";
        }

        return implode('; ', $changes);
    }

    // Helper method to get revision history
    public function revisionHistory()
    {
        return $this->hasMany(QualityCertificateRevision::class)
            ->orderBy('revision_number', 'desc');
    }

    // Get current revision
    public function currentRevision()
    {
        return $this->hasOne(QualityCertificateRevision::class)
            ->where('is_current', true);
    }
}