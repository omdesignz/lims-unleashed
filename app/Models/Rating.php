<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\QueryBuilder\AllowedFilter;

class Rating extends Model
{
    use HasFactory, SoftDeletes;

    public const MENU_NAME = 'ratings';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'rateable_id',
        'rateable_type',
        'rater_id',
        'rater_type',
        'channel',
        'criteria',
        'review',
        'metadata',
    ];

    protected $table = 'ratings';

    protected $dates = ['created_at', 'updated_at'];

    protected $casts = [
        'criteria' => 'array',
        'metadata' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

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
            AllowedFilter::partial('created_at'),
            AllowedFilter::trashed(),
        ];
    }

    public static function getAllowedSorts(): array
    {
        return [
            'id',
            'rateable_type',
            'created_at',
        ];
    }

    public static function getColumns(): array
    {
        return [
            [
                'name' => trans('gestlab.rating.labels.user'),
                'value' => 'user.name',
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
                'name' => trans('gestlab.rating.labels.review'),
                'value' => 'review',
                'filterable' => false,
                'type' => 'string',
                'format' => '',
                'filter' => '',
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

    public static function getTrashedOptions(): array
    {
        return [
            ['value' => 'only', 'text' => trans('gestlab.general.labels.trashed_only')],
            ['value' => 'with', 'text' => trans('gestlab.general.labels.trashed_with')],
        ];
    }
}
