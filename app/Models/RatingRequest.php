<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\QueryBuilder\AllowedFilter;

class RatingRequest extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'rating_requests';

    public const MENU_NAME = 'rating_requests';

    protected $dates = ['created_at', 'updated_at'];

    //
    protected $fillable = [
        'user_id',
        'rateable_id',
        'rateable_type',
        'rater_id',
        'rater_type',
        'channel',
        'status',
    ];

    public function rateable(): MorphTo
    {
        return $this->morphTo();
    }

    public function rater(): MorphTo
    {
        return $this->morphTo();
    }

    public static function getAllowedFilters(): array
    {
        return [
            AllowedFilter::exact('user_id'),
            AllowedFilter::exact('rateable_type'),
            AllowedFilter::exact('rateable_id'),
            AllowedFilter::exact('rater_type'),
            AllowedFilter::exact('rater_id'),
            AllowedFilter::exact('channel'),
            AllowedFilter::exact('status'),
            AllowedFilter::partial('created_at'),
            AllowedFilter::trashed(),
        ];
    }

    public static function getAllowedSorts(): array
    {
        return [
            'id',
            'status',
            'rateable_type',
            'created_at',
        ];
    }

    public static function getColumns(): array
    {
        return [
            [
                'name' => trans('gestlab.rating.labels.user'),
                'value' => 'user_id',
                'filterable' => true,
                'type' => 'string',
                'format' => '',
                'filter' => '',
            ],
            [
                'name' => trans('gestlab.rating.labels.rateable_type'),
                'value' => 'rateable_type',
                'filterable' => true,
                'type' => 'string',
                'format' => '',
                'filter' => '',
            ],
            [
                'name' => trans('gestlab.rating.labels.rateable_id'),
                'value' => 'rateable_id',
                'filterable' => true,
                'type' => 'string',
                'format' => '',
                'filter' => '',
            ],
            [
                'name' => trans('gestlab.rating.labels.status'),
                'value' => 'status',
                'filterable' => true,
                'type' => 'select',
                'format' => '',
                'filter' => '',
                'options' => [
                    ['value' => 'pending', 'label' => trans('gestlab.rating.status.pending')],
                    ['value' => 'completed', 'label' => trans('gestlab.rating.status.completed')],
                ],
            ],
            [
                'name' => trans('gestlab.rating.labels.channel'),
                'value' => 'channel',
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
