<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;
use App\Filters\GlobalFilter;


class OccurrenceStatus extends Model
{
    use HasFactory, SoftDeletes;

    public CONST MENU_NAME = 'occurrence_statuses';

    /**
     * The attributes that are mass assignable. 
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'description',
    ];

    protected $table = 'occurrence_statuses';
    protected $dates = ['created_at', 'updated_at', 'deleted_at'];


    public static function getAllowedFilters(): array
    {
        return [
            AllowedFilter::partial('name'),
            AllowedFilter::partial('description'),
            AllowedFilter::partial('created_at'),
            AllowedFilter::custom('globalFilter', new GlobalFilter(['name', 'description'])),
            AllowedFilter::trashed(),
        ];
    }

    public static function getAllowedSorts(): array
    {
        return [
            'name',
            'created_at',
        ];
    }

    public static function getColumns(): array
    {
        return [
                [
                    'name' => trans('gestlab.general.labels.item_statuses.name'),
                    'value' => 'name',
                    'filter_field' => 'name',
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
                    'name' => trans('gestlab.general.labels.item_statuses.description'),
                    'value' => 'description',
                    'filter_field' => 'description',
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
