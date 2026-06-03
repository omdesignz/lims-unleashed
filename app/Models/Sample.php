<?php

namespace App\Models;

use App\Filters\GlobalFilter;
use HighSolutions\EloquentSequence\Sequence;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\QueryBuilder\AllowedFilter;

class Sample extends Model
{
    use HasFactory, Sequence, SoftDeletes;

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

    public function sequence()
    {
        return [
            'group' => 'sample_month',
            'fieldName' => 'seq',
        ];
    }

    /**
     * Analysis
     *
     * @return Relationship
     */
    public function analysis()
    {
        return $this->hasOne(Analysis::class);
    }

    /**
     * Counter Analysis
     *
     * @return Relationship
     */
    public function counteranalysis()
    {
        return $this->hasOne(CounterAnalysis::class);
    }

    public function collection()
    {
        return $this->belongsTo(LabCode::class, 'cl_id');
    }

    public function scopeByParameters(Builder $query, mixed $parameters): void
    {
        $parameterIds = self::normalizeParameterIds($parameters);

        if ($parameterIds === []) {
            return;
        }

        $query->whereHas('analysis.profile.parameters', function (Builder $query) use ($parameterIds): void {
            $query->whereIn('parameter_id', $parameterIds);
        });
    }

    /**
     * Results
     *
     * @return Relationship
     */
    public function results()
    {
        return $this->hasMany(Result::class, 'sample_id');
    }

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {

            $model->seq += 1;
            $model->code = $model->sample_month.'/'.str_pad($model->seq, 4, '0', STR_PAD_LEFT);

        });

        self::deleting(function ($model) {
            // Analysis
            $model->analysis()->delete();
            // Results
            $model->results->each->forcedelete();
        });

    }

    public static function getAllowedFilters(): array
    {
        return [
            AllowedFilter::partial('collection.code'),
            AllowedFilter::partial('code'),
            AllowedFilter::partial('created_at'),
            // AllowedFilter::scope('parameters', 'by_parameters'),
            AllowedFilter::callback('parameters', function (Builder $query, mixed $value): void {
                $parameterIds = self::normalizeParameterIds($value);

                if ($parameterIds === []) {
                    return;
                }

                $query->whereHas('analysis.profile.parameters', function (Builder $query) use ($parameterIds): void {
                    $query->whereIn('parameter_id', $parameterIds);
                });
            }),
            AllowedFilter::custom('globalFilter', new GlobalFilter(['code', 'collection.code'])),
            AllowedFilter::trashed(),
        ];
    }

    public static function getAllowedSorts(): array
    {
        return [
            'collection.code',
            'created_at',
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

    /**
     * @return array<int, int>
     */
    private static function normalizeParameterIds(mixed $parameters): array
    {
        $values = is_array($parameters) ? $parameters : explode(',', (string) $parameters);

        return collect($values)
            ->flatten()
            ->flatMap(function (mixed $item): array {
                if (is_array($item)) {
                    return collect(data_get($item, 'value', $item))->flatten()->all();
                }

                return [$item];
            })
            ->filter(fn (mixed $id): bool => is_numeric($id))
            ->map(fn (mixed $id): int => (int) $id)
            ->unique()
            ->values()
            ->all();
    }
}
