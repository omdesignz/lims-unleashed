<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;
use App\Filters\GlobalFilter;


class DirectCollection extends Model
{
    use HasFactory, SoftDeletes;

    public CONST MENU_NAME = 'direct_collections';

     /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'description',
        'col_date', 
    ];

    protected $table = 'direct_collections';
    protected $dates = ['created_at', 'updated_at', 'deleted_at', 'col_date'];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        
    ];


    public function collection()
    {
        return $this->morphOne(Collection::class, 'collectionable');
    }

    public static function getAllowedFilters(): array
    {
        return [
            AllowedFilter::partial('cl.code'),
            AllowedFilter::partial('product.name'),
            AllowedFilter::partial('customer.name'),
            AllowedFilter::partial('collection.code'),
            AllowedFilter::partial('collection_date'),
            AllowedFilter::custom('globalFilter', new GlobalFilter(['lot'])),
            AllowedFilter::trashed(),
        ];
    }

    public static function getAllowedSorts(): array
    {
        return [
            'qty',
            'cl.code',
            'collection.code',
            'collection_date',
            'created_at',
        ];
    }

    public static function getColumns(): array
    {
        return [
            [
                'name' => 'QR',
                'value' => 'qr',
                'filter_field' => '',
                'filterable' => false,
                'type' => 'string',
                'format' => '',
                'filter' => '',
            ],
            [
                'name' => trans('gestlab.general.labels.direct_collections.cl'),
                'value' => 'cl',
                'filter_field' => 'collection.code',
                'filterable' => true,
                'type' => 'string',
                'format' => '',
                'filter' => '',
            ],
            [
                'name' => trans('gestlab.general.labels.direct_collections.product_id'),
                'value' => 'product',
                'filter_field' => 'product',
                'filterable' => true,
                'type' => 'string',
                'format' => '',
                'filter' => '',
                'options' => [],
                'config' => [
                    'url' => route('products.getProduct'),
                    'label' => 'name',
                    'value' => 'id',
                ]
            ],
            [
                'name' => trans('gestlab.general.labels.direct_collections.collection_date'),
                'value' => 'collection_date',
                'filter_field' => 'collection_date',
                'filterable' => true,
                'type' => 'date',
                'format' => '',
                'filter' => '',
            ],
            [
                'name' => trans('gestlab.general.labels.direct_collections.customer_id'),
                'value' => 'customer',
                'filter_field' => 'customer.name',
                'filterable' => true,
                'type' => 'string',
                'format' => '',
                'filter' => '',
                'options' => [],
                'config' => [
                    'url' => route('customers.getCustomer'),
                    'label' => 'name',
                    'value' => 'id',
                ]
            ],
            [
                'name' => trans('gestlab.general.labels.direct_collections.warehouse_id'),
                'value' => 'warehouse',
                'filter_field' => 'warehouse',
                'filterable' => false,
                'type' => 'string',
                'format' => '',
                'filter' => '',
            ],
            [
                'name' => trans('gestlab.general.labels.direct_collections.lot'),
                'value' => 'lot',
                'filter_field' => 'lot',
                'filterable' => true,
                'type' => 'string',
                'format' => '',
                'filter' => '',
            ],
            [
                'name' => trans('gestlab.general.labels.direct_collections.bl'),
                'value' => 'bl',
                'filter_field' => 'bl',
                'filterable' => true,
                'type' => 'string',
                'format' => '',
                'filter' => '',
            ],
            [
                'name' => trans('gestlab.general.labels.direct_collections.qty'),
                'value' => 'qty',
                'filter_field' => 'qty',
                'filterable' => true,
                'type' => 'string',
                'format' => '',
                'filter' => '',
            ],
            [
                'name' => trans('gestlab.general.labels.direct_collections.comercial_brand'),
                'value' => 'comercial_brand',
                'filter_field' => 'comercial_brand',
                'filterable' => true,
                'type' => 'string',
                'format' => '',
                'filter' => '',
            ],
            [
                'name' => trans('gestlab.general.labels.direct_collections.result_id'),
                'value' => 'result',
                'filter_field' => 'result',
                'filterable' => false,
                'type' => 'string',
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
        // return [
        //         [
        //             'name' => trans('gestlab.general.labels.direct_collections.description'),
        //             'value' => 'description',
        //             'filter_field' => 'description',
        //             'filterable' => true,
        //             'type' => 'string',
        //             'format' => '',
        //             'filter' => '',
        //         ],
        //         [
        //             'name' => trans('gestlab.general.labels.direct_collections.col_date'),
        //             'value' => 'col_date',
        //             'filter_field' => 'col_date',
        //             'filterable' => true,
        //             'type' => 'date',
        //             'format' => '',
        //             'filter' => '',
        //         ],
        //         [
        //             'name' => trans('gestlab.general.labels.created_at'),
        //             'value' => 'created_at',
        //             'filter_field' => 'created_at',
        //             'filterable' => true,
        //             'type' => 'date',
        //             'format' => '',
        //             'filter' => '',
        //         ],
        //         [
        //             'name' => trans('gestlab.actions.edit'),
        //             'value' => 'actions',
        //             'filter_field' => 'actions',
        //             'filterable' => false,
        //             'type' => 'actions',
        //             'format' => '',
        //             'filter' => '',
        //         ],
        //     ];
    }

    public static function getTrashedOptions(): array
    {
        return [
            ['value' => 'only', 'text' => trans('gestlab.general.labels.trashed_only')],
            ['value' => 'with', 'text' => trans('gestlab.general.labels.trashed_with')]
        ];
    }
}
