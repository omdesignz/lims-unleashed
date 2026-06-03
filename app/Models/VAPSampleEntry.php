<?php

// app/Models/SampleEntry.php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class VAPSampleEntry extends Model
{
    use SoftDeletes;

    protected $table = 'sample_entries';

    protected $fillable = [
        'name',
        'code',
        'requested_services',
        'analysis_start_date',
        'analysis_end_date',
        'status',
        'sample_year',
        'seq',
        'proposal_id',
        'collection_product_id',
        'customer_request_id',
        'customer_id',
        'warehouse_id',
        'department_id',
        'lab_id',
        'packaging_id',
        'received_by_id',
        'received_by_label',
        'sample_type',
        'received_at',
        'collected_by_lab',
        'collected_at',
        'obs',
        'client_submitted_info',
        'retention_period_days',
        'retention_due_at',
        'discard_scheduled_at',
        'retention_status',
    ];

    protected $casts = [
        'collected_by_lab' => 'boolean',
        'received_at' => 'datetime',
        'collected_at' => 'datetime',
        'analysis_start_date' => 'datetime',
        'analysis_end_date' => 'datetime',
        'requested_services' => 'array',
        'client_submitted_info' => 'array',
        'retention_due_at' => 'date',
        'discard_scheduled_at' => 'date',
    ];

    // Relationships
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function lab(): BelongsTo
    {
        return $this->belongsTo(VAPLab::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function packaging(): BelongsTo
    {
        return $this->belongsTo(PackagingCategory::class, 'packaging_id');
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by_id');
    }

    public function proposal(): BelongsTo
    {
        return $this->belongsTo(Proposal::class);
    }

    public function collectionProduct(): BelongsTo
    {
        return $this->belongsTo(CollectionProduct::class, 'collection_product_id');
    }

    public function customerRequest(): BelongsTo
    {
        return $this->belongsTo(CustomerRequest::class, 'customer_request_id');
    }

    public function discards(): HasMany
    {
        return $this->hasMany(VAPSampleDiscard::class, 'sample_id');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'POR_INICIAR');
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'EN_PROGRESO');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'COMPLETADO');
    }

    public function scopeDiscardable($query)
    {
        return $query->whereIn('status', ['COMPLETADO', 'CANCELADO']);
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * @param  Builder<VAPSampleEntry>  $query
     * @return Builder<VAPSampleEntry>
     */
    public function scopeInternalRawMaterialQualityControl(Builder $query): Builder
    {
        return $query
            ->whereIn('sample_type', ['MATERIA_PRIMA', 'RAW_MATERIAL'])
            ->where('client_submitted_info->request_origin', 'internal');
    }

    // Methods
    public function generateCode(): string
    {
        if (! $this->code) {
            $year = $this->sample_year ?? date('Y');
            $prefix = strtoupper(substr($this->sample_type ?? 'GEN', 0, 3));
            $sequence = $this->seq ?? self::where('sample_year', $year)->max('seq') + 1;

            $this->code = "SMP-{$year}-{$prefix}-".str_pad($sequence, 5, '0', STR_PAD_LEFT);
            $this->seq = $sequence;
        }

        return $this->code;
    }

    public static function defaultRetentionPeriodFor(?string $sampleType): int
    {
        return match (strtoupper((string) $sampleType)) {
            'MATERIA_PRIMA', 'RAW_MATERIAL' => 120,
            'INTERLABORATORIAL', 'PROFICIENCIA' => 180,
            'RETENCAO', 'ESTABILIDADE' => 180,
            'CONTRAPROVA', 'COUNTER_ANALYSIS' => 120,
            default => 90,
        };
    }
}
