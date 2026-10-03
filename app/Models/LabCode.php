<?php

namespace App\Models;

use App\Traits\HasScopedSequence;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Query\Builder as QueryBuilder;

class LabCode extends Model
{
    use HasFactory, HasScopedSequence, SoftDeletes;

    public const MENU_NAME = null;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'collection_id',
        'code',
        'cl_month',
        'seq',
        'codeable_id',
        'codeable_type',
    ];

    protected $table = 'lab_codes';

    protected $dates = ['created_at', 'updated_at', 'deleted_at'];

    public const STATUS_CODE_NORMAL = 'N';

    public function sequence(): array
    {
        return [
            'group' => ['cl_month', 'codeable_type'],
            'fieldName' => 'seq',
        ];
    }

    public function collection()
    {
        return $this->belongsTo(CollectionProduct::class, 'collection_id');
    }

    /**
     * Require live local ownership; archived conflicting links still make ownership ambiguous.
     */
    public function scopeForLaboratory(Builder $query, int $labId): Builder
    {
        if ($labId <= 0) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereHas('collection')
            ->whereExists(function (QueryBuilder $samples) use ($labId): void {
                $samples->selectRaw('1')->from('sample_entries')
                    ->whereColumn('sample_entries.collection_product_id', 'lab_codes.collection_id')
                    ->where('sample_entries.lab_id', $labId)
                    ->whereNull('sample_entries.deleted_at');
            })
            ->whereNotExists(function (QueryBuilder $samples) use ($labId): void {
                $samples->selectRaw('1')->from('sample_entries')
                    ->whereColumn('sample_entries.collection_product_id', 'lab_codes.collection_id')
                    ->where(function (QueryBuilder $ownership) use ($labId): void {
                        $ownership->whereNull('sample_entries.lab_id')->orWhere('sample_entries.lab_id', '!=', $labId);
                    });
            });
    }

    public function samples()
    {
        return $this->hasMany(Sample::class, 'cl_id');
    }

    public function analysis()
    {
        return $this->hasManyThrough(Analysis::class, Sample::class, 'cl_id', 'sample_id', 'id');
    }

    public function completed_analysis()
    {
        return $this->hasManyThrough(Analysis::class, Sample::class, 'cl_id', 'sample_id', 'id')->whereNotNull('end_date');
    }

    public function pending_analysis()
    {
        return $this->hasManyThrough(Analysis::class, Sample::class, 'cl_id', 'sample_id', 'id')->whereNull('init_date');
    }

    public function in_progress_analysis()
    {
        return $this->hasManyThrough(Analysis::class, Sample::class, 'cl_id', 'sample_id', 'id')->whereNull('end_date')->whereNotNull('init_date');
    }

    public function results()
    {
        return $this->hasManyThrough(Result::class, Sample::class, 'cl_id', 'sample_id', 'id');
    }

    public function latest_inserted_result()
    {
        return $this->hasManyThrough(Result::class, Sample::class, 'cl_id', 'sample_id', 'id')->whereNotNull('inserted_date')->latest() ?? [];
    }

    public function latest_verified_result()
    {
        return $this->hasManyThrough(Result::class, Sample::class, 'cl_id', 'sample_id', 'id')->whereNotNull('verified_date')->latest() ?? [];
    }

    public function latest_approved_result()
    {
        return $this->hasManyThrough(Result::class, Sample::class, 'cl_id', 'sample_id', 'id')->whereNotNull('approved_date')->latest() ?? [];
    }

    public function quality_certificate()
    {
        return $this->hasManyThrough(QualityCertificate::class, CollectionProduct::class, 'collection_id', 'collection_id', 'id');
    }

    public function codeable()
    {
        return $this->morphTo();
    }

    public static function boot(): void
    {
        parent::boot();

        static::creating(function (LabCode $labCode): void {
            $labCode->code = $labCode->cl_month.'/'.str_pad((string) $labCode->seq, 4, '0', STR_PAD_LEFT);
        });

    }
}
