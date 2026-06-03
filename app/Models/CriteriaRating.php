<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\QueryBuilder\AllowedFilter;

class CriteriaRating extends Model
{
    use SoftDeletes;

    public const MENU_NAME = 'criteria_rating';

    protected $table = 'criteria_rating';

    protected $fillable = [
        'name',
        'description',
        'type',
    ];

    public static function getAllowedFilters(): array
    {
        return [
            AllowedFilter::partial('name'),
            AllowedFilter::partial('description'),
            AllowedFilter::exact('type'),
            AllowedFilter::partial('created_at'),
            AllowedFilter::trashed(),
        ];
    }

    public static function getAllowedSorts(): array
    {
        return [
            'name',
            'type',
            'created_at',
        ];
    }

    public static function getColumns(): array
    {
        return [
            [
                'name' => trans('gestlab.rating.labels.name'),
                'value' => 'name',
                'filterable' => true,
                'type' => 'string',
                'format' => '',
                'filter' => '',
            ],
            [
                'name' => trans('gestlab.rating.labels.description'),
                'value' => 'description',
                'filterable' => true,
                'type' => 'string',
                'format' => '',
                'filter' => '',
            ],
            [
                'name' => trans('gestlab.rating.labels.type'),
                'value' => 'type',
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
}
