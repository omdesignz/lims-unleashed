<?php

namespace App\Models;

use Spatie\Activitylog\Models\Activity;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ISOActivityLog extends Activity
{
    protected $table = 'activity_log';
    
    protected $appends = ['iso_compliant', 'change_context'];
    
    // Add custom ISO fields to casts
    protected $casts = [
        'properties' => 'collection',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'custom_fields' => 'array',
    ];
    
    /**
     * Relationship to quality certificate (if applicable)
     */
    public function qualityCertificate(): BelongsTo
    {
        return $this->belongsTo(QualityCertificate::class, 'subject_id')
            ->where('subject_type', QualityCertificate::class);
    }
    
    /**
     * Scope for ISO 17025 related activities
     */
    public function scopeIsoCertificates($query)
    {
        return $query->where('subject_type', QualityCertificate::class)
            ->orWhere('subject_type', CollectionProduct::class)
            ->orWhere('subject_type', Result::class);
    }
    
    /**
     * Scope for certificate revisions
     */
    public function scopeCertificateRevisions($query, $certificateId)
    {
        return $query->where('subject_type', QualityCertificateRevision::class)
            ->whereHas('subject', function ($q) use ($certificateId) {
                $q->where('quality_certificate_id', $certificateId);
            });
    }
    
    /**
     * Accessor for ISO compliance flag
     */
    public function getIsoCompliantAttribute(): bool
    {
        $requiredProperties = ['change_reason', 'user_id', 'timestamp'];
        
        foreach ($requiredProperties as $property) {
            if (empty($this->properties->get($property))) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Accessor for change context
     */
    public function getChangeContextAttribute(): string
    {
        $context = $this->description;
        
        if ($reason = $this->properties->get('change_reason')) {
            $context .= " - Reason: {$reason}";
        }
        
        return $context;
    }
}