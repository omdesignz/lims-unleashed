<?php

namespace App\Models;

use App\Filters\GlobalFilter;
use Illuminate\Database\Eloquent\Model;
use Spatie\QueryBuilder\AllowedFilter;

class Notification extends Model
{
    public const MENU_NAME = 'notifications';
    protected $fillable = ['user_id', 'title', 'message', 'is_read'];
    

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public static function getAllowedFilters(): array
    {
        return [
            AllowedFilter::partial('data'),
            AllowedFilter::partial('created_at'),
            AllowedFilter::custom('globalFilter', new GlobalFilter(['data'])),
        ];
    }

    public static function getAllowedSorts(): array
    {
        return [
            'created_at',
        ];
    }

    public static function getColumns(): array
    {
        return [
                
                [
                    'name' => trans('gestlab.general.labels.notifications.title'),
                    'value' => 'title',
                    'filter_field' => 'title',
                    'filterable' => false,
                    'type' => 'string',
                    'format' => '',
                    'filter' => '',
                ],
                [
                    'name' => trans('gestlab.general.labels.notifications.message'),
                    'value' => 'message',
                    'filter_field' => 'message',
                    'filterable' => false,
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
}
