<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;
use App\Filters\GlobalFilter;


class OccurrenceOrigin extends Model
{
    use HasFactory, SoftDeletes;

    public const MENU_NAME = 'occurrence_origins';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'description',
        'code',
    ];

    protected $table = 'occurrence_origins';
    protected $dates = ['created_at', 'updated_at'];

    public static function getAllowedFilters(): array
    {
        return [
            AllowedFilter::partial('name'),
            AllowedFilter::partial('description'),
            AllowedFilter::partial('code'),
            AllowedFilter::partial('created_at'),
            AllowedFilter::custom('globalFilter', new GlobalFilter(['name', 'description', 'code'])),
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
                    'name' => trans('gestlab.general.labels.occurrence_origins.name'),
                    'value' => 'name',
                    'filterable' => true,
                    'type' => 'select',
                    'format' => '',
                    'filter' => '',
                    'options' => [
                        ['value' => 'pending', 'label' => 'Pending'],
                        ['value' => 'approved', 'label' => 'Approved'],
                        ['value' => 'rejected', 'label' => 'Rejected']
                    ],
                    'config' => [
                        'url' => route('customers.getCustomer'),
                        'label' => 'name',
                        'value' => 'id',
                    ]
                ],
                [
                    'name' => trans('gestlab.general.labels.occurrence_origins.description'),
                    'value' => 'description',
                    'filterable' => true,
                    'type' => 'string',
                    'format' => '',
                    'filter' => '',
                ],
                [
                    'name' => trans('gestlab.general.labels.created_at'),
                    'value' => 'created_at',
                    'filterable' => true,
                    'type' => 'date',
                    'format' => '',
                    'filter' => '',
                ],
                // [
                //     'name' => trans('gestlab.general.labels.trashed'),
                //     'value' => 'trashed',
                //     'filterable' => true,
                //     'type' => 'select',
                //     'format' => '',
                //     'filter' => '',
                //     'options' => [
                //         ['value' => 'only', 'text' => trans('gestlab.general.labels.trashed_only')],
                //         ['value' => 'with', 'text' => trans('gestlab.general.labels.trashed_with')]
                //     ]
                // ],
                [
                    'name' => trans('gestlab.actions.edit'),
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
