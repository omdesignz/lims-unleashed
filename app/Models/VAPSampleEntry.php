<?php

// app/Models/SampleEntry.php

namespace App\Models;

use App\Services\ScopedSequenceAllocator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use LogicException;

class VAPSampleEntry extends Model
{
    use HasFactory;
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
        'seq' => 'integer',
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

    protected static function booted(): void
    {
        static::creating(function (self $sample): void {
            if ($sample->lab_id === null) {
                throw new LogicException('A laboratory is required before a sample number can be issued.');
            }

            $sample->generateCode();
        });

        static::updating(function (self $sample): void {
            if ($sample->getOriginal('lab_id') !== null && $sample->isDirty('lab_id')) {
                throw new LogicException('The laboratory owning a sample cannot be changed.');
            }

            if ($sample->getOriginal('seq') !== null && $sample->isDirty(['seq', 'sample_year'])) {
                throw new LogicException('Issued sample sequence numbers and their year cannot be changed.');
            }

            if (filled($sample->getOriginal('code')) && $sample->isDirty('code')) {
                throw new LogicException('An issued sample code cannot be changed.');
            }

            if ($sample->getOriginal('collection_product_id') !== null) {
                $sample->ensureIssuedIdentityIsUnchanged();
            }
        });
    }

    private function ensureIssuedIdentityIsUnchanged(): void
    {
        if ($this->isDirty(['collection_product_id', 'customer_id', 'warehouse_id', 'department_id', 'sample_type', 'requested_services', 'customer_request_id'])) {
            throw new LogicException('The identity of an accessioned sample cannot be changed.');
        }

        $original = $this->getOriginal('client_submitted_info') ?? [];
        $current = $this->client_submitted_info ?? [];
        $initialLinksAreValid = $this->canInitializeAnalyticalLinks($original, $current);

        foreach (['product_id', 'matrix_id', 'collection_type', 'request_origin', 'analysis_discipline', 'batch_sample_index', 'batch_sample', 'requested_profile_ids', 'linked_lab_code_id', 'linked_sample_ids',
            'linked_collection_type', 'resolved_profile_ids', 'resolved_profiles', 'required_parameters', 'required_parameter_count'] as $field) {
            $oldValue = $original[$field] ?? null;
            $newValue = $current[$field] ?? null;

            if ($field === 'request_origin') {
                $oldValue ??= 'client';
                $newValue ??= 'client';
            }

            if ($field === 'requested_profile_ids') {
                $oldValue = collect($oldValue ?? [])->map(fn (mixed $id): int => (int) $id)->unique()->sort()->values()->all();
                $newValue = collect($newValue ?? [])->map(fn (mixed $id): int => (int) $id)->unique()->sort()->values()->all();
            }

            if ($oldValue !== $newValue) {
                if ($initialLinksAreValid && (in_array($field, ['linked_lab_code_id', 'linked_sample_ids', 'linked_collection_type'], true)
                    || ($field === 'requested_profile_ids' && empty($original['requested_profile_ids'])))) {
                    continue;
                }

                throw new LogicException('The analytical scope and issued links of an accessioned sample cannot be changed.');
            }
        }
    }

    /**
     * @param  array<string, mixed>  $original
     * @param  array<string, mixed>  $current
     */
    private function canInitializeAnalyticalLinks(array $original, array $current): bool
    {
        $ids = $current['linked_sample_ids'] ?? [];
        $codeId = filter_var($current['linked_lab_code_id'] ?? null, FILTER_VALIDATE_INT);

        if ($this->trashed() || filled($original['linked_lab_code_id'] ?? null) || ! empty($original['linked_sample_ids'])
            || filled($original['linked_collection_type'] ?? null) || ! $codeId || ! is_array($ids) || $ids === []
            || ! collect($ids)->every(fn (mixed $id): bool => filter_var($id, FILTER_VALIDATE_INT) !== false && $id > 0)) {
            return false;
        }

        if (! VAPLab::query()->whereKey($this->lab_id)->exists()
            || self::withTrashed()->where('collection_product_id', $this->collection_product_id)->whereKeyNot($this->id)->exists()) {
            return false;
        }

        $record = CollectionProduct::query()->with(['collection.collectionable', 'product.matrix.profiles.type'])
            ->where('customer_id', $this->customer_id)->where('warehouse_id', $this->warehouse_id)->find($this->collection_product_id);
        $code = LabCode::query()->where('collection_id', $this->collection_product_id)->where('codeable_type', 'analysis')->find($codeId);

        if (! $record?->collection?->collectionable || ! $code
            || ! in_array($record->collection->collectionable_type, ['direct', 'programmed'], true)
            || $record->collection->collectionable_type !== ($current['linked_collection_type'] ?? null)
            || (int) $record->collection->customer_id !== (int) $this->customer_id
            || (int) $record->collection->warehouse_id !== (int) $this->warehouse_id
            || (isset($original['collection_type']) && $original['collection_type'] !== $record->collection->collectionable_type)
            || (isset($original['product_id']) && (int) $original['product_id'] !== (int) $record->product_id)
            || (isset($original['matrix_id']) && (int) $original['matrix_id'] !== (int) $record->product?->matrix_id)
            || LabCode::withTrashed()->where('collection_id', $this->collection_product_id)
                ->where('codeable_type', 'analysis')->whereKeyNot($codeId)->exists()) {
            return false;
        }

        $sampleIds = collect($ids)->map(fn (mixed $id): int => (int) $id)->unique()->sort()->values()->all();
        $actualSampleIds = $code->samples()->withTrashed()->orderBy('id')->pluck('id')->all();

        if (count($ids) !== count($sampleIds) || $sampleIds !== $actualSampleIds || $code->samples()->onlyTrashed()->exists()) {
            return false;
        }

        $analyses = Analysis::withTrashed()->where(fn (Builder $query): Builder => $query
            ->where('cl_id', $codeId)->orWhereIn('sample_id', $sampleIds))->get();
        $profiles = ($record->product?->matrix?->profiles ?? collect())->keyBy('id');

        if ($analyses->count() !== count($sampleIds) || $analyses->pluck('sample_id')->unique()->count() !== count($sampleIds)
            || $analyses->contains(fn (Analysis $analysis): bool => $analysis->trashed()
                || (int) $analysis->cl_id !== $codeId || ! in_array($analysis->sample_id, $sampleIds, true)
                || (int) $analysis->product_id !== (int) $record->product_id
                || (int) $analysis->department_id !== (int) $this->department_id
                || ! $profiles->has($analysis->profile_id)
                || (int) $analysis->type_id !== (int) $profiles->get($analysis->profile_id)?->category_id)) {
            return false;
        }

        $profileIds = $analyses->pluck('profile_id')->unique()->sort()->values()->all();
        $allowedIds = $profiles
            ->filter(fn (Profile $profile): bool => (int) $profile->type?->department_id === (int) $this->department_id)->pluck('id');
        $requestedIds = collect($current['requested_profile_ids'] ?? [])->map(fn (mixed $id): int => (int) $id)->unique()->sort()->values()->all();
        $issuedScope = collect($original['resolved_profile_ids'] ?? $original['requested_profile_ids'] ?? [])
            ->map(fn (mixed $id): int => (int) $id)->unique()->sort()->values();

        return count($profileIds) === count($sampleIds) && $profileIds === $requestedIds && collect($profileIds)->diff($allowedIds)->isEmpty()
            && ($issuedScope->isEmpty() || $issuedScope->all() === $profileIds);
    }

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
        if (filled($this->code)) {
            return $this->code;
        }

        if ($this->exists && $this->seq !== null && $this->sample_year === null) {
            throw new LogicException('A legacy sample sequence without a year requires an explicit data repair.');
        }

        $this->sample_year ??= now()->format('Y');
        $prefix = Str::upper(Str::substr($this->sample_type ?: 'GEN', 0, 3));
        $allocator = new ScopedSequenceAllocator;
        $hasExplicitSequence = $this->seq !== null && $this->seq > 0;
        $scope = $this->lab_id === null ? ['sample_year'] : ['lab_id', 'sample_year'];

        if (! $this->exists || $this->seq === null) {
            $allocator->assign($this, ['group' => $scope]);
        }

        do {
            $labToken = $this->lab_id === null ? '' : '-L'.$this->lab_id;
            $code = 'SMP-'.$this->sample_year.$labToken.'-'.$prefix.'-'.str_pad((string) $this->seq, 5, '0', STR_PAD_LEFT);
            $collision = $this->newQueryWithoutScopes()->where('code', $code)
                ->when($this->exists, fn (Builder $query) => $query->whereKeyNot($this->getKey()))->exists();

            if ($collision) {
                if ($hasExplicitSequence) {
                    throw new LogicException('The code for this explicit sample sequence has already been issued.');
                }

                $this->seq = null;
                $allocator->assign($this, ['group' => $scope]);
            }
        } while ($collision);

        $this->code = $code;

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
