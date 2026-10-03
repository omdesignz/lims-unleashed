<?php

namespace App\Models;

use App\Support\SpecimenParameterSelection;
use App\Traits\HasScopedSequence;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;

class Sample extends Model
{
    use HasFactory, HasScopedSequence, SoftDeletes;

    public const MENU_NAME = 'samples';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'cl_id',
        'sample_month',
        'code',
        'seq',
    ];

    protected $table = 'samples';

    protected $dates = ['created_at', 'updated_at', 'deleted_at'];

    public function sequence(): array
    {
        return [
            'group' => 'sample_month',
            'fieldName' => 'seq',
        ];
    }

    public function analysis(): HasOne
    {
        return $this->hasOne(Analysis::class);
    }

    public function counteranalysis(): HasOne
    {
        return $this->hasOne(CounterAnalysis::class);
    }

    public function collection(): BelongsTo
    {
        return $this->belongsTo(LabCode::class, 'cl_id');
    }

    public function scopeByParameters(Builder $query, mixed $parameters): void
    {
        $parameterIds = SpecimenParameterSelection::normalize($parameters);

        if ($parameterIds === []) {
            return;
        }

        $query->whereHas('analysis', fn (Builder $analysis): Builder => $analysis
            ->whereColumn('analysis.cl_id', 'samples.cl_id')
            ->whereHas('sample.collection.collection.sampleEntry', function (Builder $entry) use ($parameterIds): void {
                $entry->where(function (Builder $scope) use ($parameterIds): void {
                    foreach ($parameterIds as $parameterId) {
                        $scope->orWhereRaw("(sample_entries.client_submitted_info::jsonb -> 'required_parameters') @> jsonb_build_array(jsonb_build_object('id', CAST(? AS BIGINT), 'profile_ids', jsonb_build_array(analysis.profile_id)))", [$parameterId]);
                    }
                });
            }));
    }

    public function scopeBySearch(Builder $query, string $search): void
    {
        if ($search === '') {
            return;
        }

        $like = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search).'%';
        $query->where(fn (Builder $matches): Builder => $matches
            ->whereLike('samples.code', $like)
            ->orWhereHas('collection', fn (Builder $code): Builder => $code->whereLike('lab_codes.code', $like)));
    }

    public function results(): HasMany
    {
        return $this->hasMany(Result::class, 'sample_id');
    }

    public static function boot(): void
    {
        parent::boot();

        static::creating(function (Sample $sample): void {
            $sample->code = $sample->sample_month.'/'.str_pad((string) $sample->seq, 4, '0', STR_PAD_LEFT);
        });

    }

    public static function getAllowedFilters(): array
    {
        return [
            AllowedFilter::partial('collection.code'),
            AllowedFilter::partial('code'),
            AllowedFilter::callback('created_at', fn (Builder $query, string $value): Builder => $query->whereDate('samples.created_at', $value)),
            AllowedFilter::callback('parameters', function (Builder $query, mixed $value): void {
                $query->byParameters($value);
            }),
            AllowedFilter::callback('globalFilter', fn (Builder $query, mixed $value): Builder => $query->bySearch(self::filterText($value))),
            AllowedFilter::trashed(),
        ];
    }

    public static function getAllowedSorts(): array
    {
        return [
            AllowedSort::callback('collection.code', fn (Builder $query, bool $descending): Builder => $query
                ->orderBy(LabCode::query()->select('code')->whereColumn('lab_codes.id', 'samples.cl_id'), $descending ? 'desc' : 'asc')),
            AllowedSort::field('code', 'samples.code'),
            AllowedSort::field('created_at', 'samples.created_at'),
        ];
    }

    public static function getColumns(): array
    {
        return [
            [
                'name' => trans('gestlab.general.labels.samples.cl_id'),
                'value' => 'collection',
                'filter_field' => 'collection.code',
                'filterable' => true,
                'type' => 'string',
                'format' => '',
                'filter' => '',
                'options' => [
                    // ['value' => 'pending', 'label' => 'Pending'],
                    // ['value' => 'approved', 'label' => 'Approved'],
                    // ['value' => 'rejected', 'label' => 'Rejected']
                ],
                'config' => [
                    'url' => route('customers.getCustomer'),
                    'label' => 'name',
                    'value' => 'id',
                ],
            ],
            [
                'name' => trans('gestlab.general.labels.samples.code'),
                'value' => 'code',
                'filter_field' => 'code',
                'filterable' => true,
                'type' => 'string',
                'format' => '',
                'filter' => '',
            ],
            [
                'name' => trans('gestlab.general.labels.created_at'),
                'value' => 'created_at',
                'filter_field' => 'created_at',
                'filterable' => true,
                'type' => 'date',
                'format' => '',
                'filter' => '',
            ],
        ];
    }

    public static function getTrashedOptions(): array
    {
        return [
            ['value' => 'only', 'text' => trans('gestlab.general.labels.trashed_only')],
            ['value' => 'with', 'text' => trans('gestlab.general.labels.trashed_with')],
        ];
    }

    private static function filterText(mixed $value): string
    {
        $parts = is_array($value) ? $value : [$value];

        return implode(',', array_map(fn (mixed $part): string => is_bool($part)
            ? ($part ? 'true' : 'false')
            : (string) $part, $parts));
    }
}
