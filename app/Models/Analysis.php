<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;
use App\Filters\GlobalFilter;
use Carbon\Carbon;

// use App\Filters\AnalysisByParametersFilter;


class Analysis extends Model
{
    use HasFactory, SoftDeletes;

    public CONST MENU_NAME = 'analysis';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'department_id',
        'sample_id',
        'profile_id',
        'type_id',
        'cl_id',
        'product_id',
        'status',
        'entry_date',
        'init_date',
        'col_date',
        'end_date',
    ];

    protected $table = 'analysis';
    protected $dates = ['created_at', 'updated_at', 'deleted_at', 'entry_date', 'init_date', 'col_date', 'end_date'];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'status' => 'boolean',
    ];

    public function result()
    {
        return $this->morphOne(Result::class, 'resultable');
    }

    public function codeable()
    {
        return $this->morphOne(LabCode::class, 'codeable');
    }

    public function code()
    {
        return $this->belongsTo(LabCode::class, 'cl_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /**
     * Department
     *
     * @return Relationship
     */
    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Analysis Type
     *
     * @return Relationship
     */
    public function type()
    {
        return $this->belongsTo(AnalysisCategory::class, 'type_id');
    }

    /**
     * Profile
     *
     * @return Relationship
     */
    public function profile()
    {
        return $this->belongsTo(Profile::class);
    }

    /**
     * Sample
     *
     * @return Relationship
     */
    public function sample()
    { 
        return $this->belongsTo(Sample::class);
    }

    // public function scopeInsert($query) {
        
    //     return $this->whereHas('sample', function($q) {
    //         $q->doesnthave('results');
    //         $q->whereNull('end_date');
    //     });
    // }

    public function results()
    {
        return $this->hasManyThrough(Result::class, Sample::class, 'id', 'sample_id', 'id'); 
    }

    public function scopeInsert($query) 
    {
        return $query->whereHas('sample', function($q) {
            $q->whereDoesntHave('results');
        })
        ->whereNull('end_date'); // ✅ Already correct
    }

    public function scopeVerify($query) 
    {
        return $query->whereHas('sample', function($q) {
            $q->whereHas('results', function($q) {
                $q->whereNotNull('inserted_date')
                ->whereNull('verified_date');
            })
            ->whereDoesntHave('results', function($q) {
                $q->whereNotNull('verified_date');
            });
        })
        ->whereNull('end_date'); // ✅ Exclude archived
    }

    public function scopeApprove($query) 
    {
        return $query->whereHas('sample', function($q) {
            $q->whereHas('results', function($q) {
                $q->whereNotNull('verified_date')
                ->whereNull('approved_date');
            })
            ->whereDoesntHave('results', function($q) {
                $q->whereNotNull('approved_date');
            })
            ->whereDoesntHave('results', function($q) {
                $q->whereNull('verified_date');
            });
        })
        ->whereNull('end_date'); // ✅ Exclude archived
    }
    
    // public function scopeVerify($query) {
        
    //     return $this->whereHas('sample', function(Builder $q) {
    //         $q->whereHas('results', function(Builder $q) {
    //             $q->whereNotNull('inserted_value');
    //             $q->whereNull('verified_value');
    //             $q->whereNull('approved_value');
    //         });
    //     });
    // }
    
    // public function scopeApprove($query) {
        
    //     return $this->whereHas('sample', function(Builder $q) {
    //         $q->whereHas('results', function(Builder $q) {
    //             $q->whereNotNull('inserted_value');
    //             $q->whereNotNull('verified_value');
    //             $q->whereNull('approved_value');
    //         });
    //     });
    // }

    // public function scopeArchived($query) {
        
    //     return $this->whereNotNull('end_date');
    // }

    public function scopeArchived($query) 
    {
        return $query->whereNotNull('end_date')
            ->whereHas('sample', function($q) {
                // Ensure all results are fully approved (no pending work)
                $q->whereDoesntHave('results', function($q) {
                    $q->whereNull('approved_date'); // No unapproved results
                });
            });
    }

    public function scopeByParameters(Builder $q, $parameters) : void {
        
         $q->whereHas('profile', function($q) use ($parameters) {
                $q->whereHas('parameters', function($q) use ($parameters) {
                $q->whereIn('parameter_id', explode(',', $parameters));
            });
        });
    }

    // public static function getAllowedFilters(): array
    // {
    //     return [
    //         AllowedFilter::partial('entry_date'),
    //         AllowedFilter::partial('init_date'),
    //         // AllowedFilter::partial('col_date'),
    //         AllowedFilter::callback('col_date', function($query, $value) {
    //             // dd($value['start'], $value['end']);
    //             if(empty($value['start']) || empty($value['end'])){
    //                 return $query;
    //             }
    //             // $start = explode(',', $value['start'])[0];
    //             // $end = explode(',', $value['end'])[0];

    //             // dd($start, $end);

    //             return $query->whereBetween('col_date', [Carbon::parse($value['start'])->startOfDay(), Carbon::parse($value['end'])->endOfDay()]);
                
    //             // if($value == 'today'){
    //             //     $query->whereBetwee('col_date', '=', date('Y-m-d'));
    //             // }
    //         }),
    //         AllowedFilter::partial('end_date'),
    //         AllowedFilter::partial('code.code'),
    //         AllowedFilter::partial('product.name'),
    //         AllowedFilter::partial('status'),
    //         // AllowedFilter::callback('category', function($query, $value) {
    //         //     if($value == 'insert'){
    //         //         $query->Insert();
    //         //     }

    //         //     if($value == 'verify'){
    //         //         return $query->Verify();
    //         //     }

    //         //     if($value == 'approve'){
    //         //         return $query->Approve();
    //         //     }
    //         // }),
    //         AllowedFilter::callback('category', function($query, $value) {
    //             match($value) {
    //                 'insert' => $query->Insert(),
    //                 'verify' => $query->Verify(),
    //                 'approve' => $query->Approve(),
    //                 'archived' => $query->Archived(),
    //                 default => $query->Insert(),
    //             };
    //         }),
    //         AllowedFilter::partial('department.name'),
    //         AllowedFilter::partial('created_at'),
    //         AllowedFilter::custom('globalFilter', new GlobalFilter(['entry_date'])),
    //         AllowedFilter::trashed(),
    //     ];
    // }

    public static function getAllowedFilters(): array
{
    return [
        AllowedFilter::partial('entry_date'),
        AllowedFilter::partial('init_date'),
        AllowedFilter::callback('col_date', function($query, $value) {
            if(empty($value['start']) || empty($value['end'])){
                return $query;
            }
            return $query->whereBetween('col_date', [
                Carbon::parse($value['start'])->startOfDay(), 
                Carbon::parse($value['end'])->endOfDay()
            ]);
        }),
        AllowedFilter::partial('end_date'),
        AllowedFilter::partial('code.code'),
        AllowedFilter::partial('product.name'),
        AllowedFilter::partial('status'),
        
        // FIXED: Remove return statements, call scopes directly on $query
        // AllowedFilter::callback('category', function($query, $value) {
        //     match($value) {
        //         'insert' => $query->Insert(),
        //         'verify' => $query->Verify(),
        //         'approve' => $query->Approve(),
        //         'archived' => $query->Archived(),
        //         default => $query->Insert(), // Default fallback
        //     };
        // }),
        
        AllowedFilter::partial('department.name'),
        AllowedFilter::partial('created_at'),
        AllowedFilter::custom('globalFilter', new GlobalFilter(['entry_date'])),
        AllowedFilter::trashed(),
    ];
}

    public static function getAllowedSorts(): array
    {
        return [
            'created_at',
            'department.name',
            'profile.name',
            'col_date',
            'status',
        ];
    }

    public static function getColumns(): array
    {
        return [
                [
                    'name' => trans('gestlab.general.labels.analysis.cl_id'),
                    'value' => 'cl',
                    'filter_field' => 'code.code',
                    'filterable' => true,
                    'type' => 'string',
                    'format' => '',
                    'filter' => '',
                ],
                [
                    'name' => trans('gestlab.general.labels.analysis.department_id'),
                    'value' => 'department',
                    'filter_field' => 'department.name',
                    'filterable' => true,
                    'type' => 'string',
                    'format' => '',
                    'filter' => '',
                    'options' => [
                        
                    ],
                    'config' => [
                        'url' => route('departments.getDepartment'),
                        'label' => 'name',
                        'value' => 'id',
                    ],
                ],
                // [
                //     'name' => trans('gestlab.general.labels.analysis.profile_id'),
                //     'value' => 'profile',
                //     'filter_field' => 'profile.name',
                //     'filterable' => true,
                //     'type' => 'string',
                //     'format' => '',
                //     'filter' => '',
                //     'options' => [
                        
                //     ],
                //     'config' => [
                //         'url' => route('profiles.getProfile'),
                //         'label' => 'name',
                //         'value' => 'id',
                //     ],
                // ],
                [
                    'name' => trans('gestlab.general.labels.analysis.product_id'),
                    'value' => 'product',
                    'filter_field' => 'product.name',
                    'filterable' => true,
                    'type' => 'string',
                    'format' => '',
                    'filter' => '',
                    'options' => [
                        
                    ],
                    'config' => [
                        'url' => route('products.getProduct'),
                        'label' => 'name',
                        'value' => 'id',
                    ],
                ],
                [
                    'name' => trans('gestlab.general.labels.analysis.col_date'),
                    'value' => 'col_date',
                    'filter_field' => 'col_date',
                    'filterable' => true,
                    'type' => 'date',
                    'format' => '',
                    'filter' => '',
                ],
                [
                    'name' => trans('gestlab.general.labels.analysis.status'),
                    'value' => 'status',
                    'filter_field' => 'status',
                    'filterable' => true,
                    'type' => 'boolean',
                    'format' => '',
                    'filter' => '',
                ],
                // [
                //     'name' => trans('gestlab.general.labels.created_at'),
                //     'value' => 'created_at',
                //     'filterable' => true,
                //     'type' => 'date',
                //     'format' => '',
                //     'filter' => '',
                // ],
                [
                    'name' => trans('gestlab.actions.action'),
                    'value' => 'actions',
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
