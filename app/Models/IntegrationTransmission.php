<?php

namespace App\Models;

use Database\Factories\IntegrationTransmissionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IntegrationTransmission extends Model
{
    /** @use HasFactory<IntegrationTransmissionFactory> */
    use HasFactory;

    public const STATUSES = [
        'received',
        'matched',
        'quarantined',
        'imported',
        'rejected',
        'failed',
    ];

    protected $fillable = [
        'connector_id',
        'mapping_id',
        'matched_sample_id',
        'matched_parameter_id',
        'matched_result_id',
        'reviewed_by_id',
        'external_id',
        'correlation_id',
        'direction',
        'status',
        'checksum',
        'content_type',
        'raw_payload',
        'normalized_payload',
        'sample_code',
        'parameter_code',
        'measured_value',
        'measured_unit',
        'measured_at',
        'diagnostics',
        'received_at',
        'processed_at',
        'reviewed_at',
        'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'normalized_payload' => 'array',
            'diagnostics' => 'array',
            'measured_at' => 'datetime',
            'received_at' => 'datetime',
            'processed_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    public function connector(): BelongsTo
    {
        return $this->belongsTo(IntegrationConnector::class, 'connector_id');
    }

    public function mapping(): BelongsTo
    {
        return $this->belongsTo(IntegrationMapping::class, 'mapping_id');
    }

    public function sample(): BelongsTo
    {
        return $this->belongsTo(Sample::class, 'matched_sample_id');
    }

    public function parameter(): BelongsTo
    {
        return $this->belongsTo(Parameter::class, 'matched_parameter_id');
    }

    public function result(): BelongsTo
    {
        return $this->belongsTo(Result::class, 'matched_result_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_id');
    }
}
