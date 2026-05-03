<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use HighSolutions\EloquentSequence\Sequence;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;
use App\Filters\GlobalFilter;
use Illuminate\Database\Eloquent\Builder;


class Sample extends Model
{
    use HasFactory, SoftDeletes, Sequence;

    public CONST MENU_NAME = 'samples';

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

    public function scopeByParameters(Builder $q, $parameters) : void {
        
        $this->analysis()->whereHas('profile', function($q) use ($parameters) {
               $q->whereHas('parameters', function($q) use ($parameters) {
               $q->whereIn('parameter_id', explode(',', $parameters));
           });
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

            static::creating(function($model) {

                $model->seq += 1; 
                $model->code = $model->sample_month . '/' . str_pad ($model->seq, 4, '0', STR_PAD_LEFT);
        
            });

            self::deleting(function($model) {
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
            AllowedFilter::partial('created_at'),
            // AllowedFilter::scope('parameters', 'by_parameters'),
            AllowedFilter::callback('parameters', function($query, $value) {
                 $query->whereHas('analysis', function($q) use ($value) {
                    $q->whereHas('profile', function($q) use ($value) {
                        $q->whereHas('parameters', function($q) use ($value) {
                            
                            // dd(collect($value)->filter(function($item) {
                            //     return array_key_exists('value', $item);
                            // })->map->value->unique()->values());
                            
                            is_array($value) ? $q->whereIn('parameter_id', collect($value)->filter(fn($item) => array_key_exists('value', $item))->collapse()->unique()->values()) : $q->whereIn('parameter_id', explode(',', $value));
                            // $q->whereIn('parameter_id', explode(',', $value));
                        });
                    });
                });
            }),
            AllowedFilter::custom('globalFilter', new GlobalFilter(['name', 'description', 'collection.code'])),
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
                    ]
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
                [
                    'name' => trans('gestlab.actions.edit'),
                    'value' => 'actions',
                    'filter_field' => 'actions',
                    'filterable' => false,
                    'type' => 'actions',
                    'format' => '',
                    'filter' => '',
                ],
            ];
    }

    public static function getTrashedOptions(): array
    {
        return [
            ['value' => 'only', 'text' => trans('gestlab.general.labels.trashed_only')],
            ['value' => 'with', 'text' => trans('gestlab.general.labels.trashed_with')]
        ];
    }
}
