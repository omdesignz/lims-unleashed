<?php

namespace App\Models;

use App\Enums\Collections\CollectionProductTrackingStatus;
use App\Filters\GlobalFilter;
use App\Traits\ISO17025Revisionable;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Label\Label;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\SvgWriter;
use Illuminate\Database\Eloquent\Casts\AsCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\CausesActivity;
use Spatie\QueryBuilder\AllowedFilter;

class CollectionProduct extends Model
{
    use CausesActivity, HasFactory, ISO17025Revisionable, SoftDeletes;

    public const MENU_NAME = null;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'collection_id',
        'pack_id',
        'owner_id',
        'customer_id',
        'warehouse_id',
        'product_id',
        'temperature_id',
        'invoice_id',
        'quote_id',
        'result_id',
        'vehicle_id',
        'comercial_brand',
        'du_no',
        'progress',
        'term_no',
        'container_no',
        'temperature_value',
        'recollection',
        'obs',
        'processed',
        'collected_by_lab',
        'expiry_date',
        'production_date',
        'collection_date',
        'qty',
        'collected_qty',
        'origin',
        'location',
        'lot',
        'bl',
        'invoiced',
        'quoted',
        'status',
        'sample_status',
        'sampling_plan_ref',
        'customer_submitted_info',
        'analysis_start_date',
        'analysis_end_date',
        'extra_data',
    ];

    protected $table = 'collection_product';

    protected $dates = ['created_at', 'updated_at', 'deleted_at', 'expiry_date', 'collection_date', 'end_date', 'production_date'];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'status' => 'boolean',
        'invoiced' => 'boolean',
        'quoted' => 'boolean',
        'processed' => 'boolean',
        'collected_by_lab' => 'boolean',
        'recollection' => 'boolean',
        'progress' => CollectionProductTrackingStatus::class,
        'extra_data' => AsCollection::class,
    ];

    /**
     * Collection
     *
     * @return Relationship
     */
    public function collection()
    {
        return $this->belongsTo(Collection::class);
    }

    /**
     * Product
     *
     * @return Relationship
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function getQrAttribute()
    {
        $writer = new SvgWriter;

        $text = <<< EOT
                    {$this->code->code}
                    {$this->product->name}
                EOT;

        $qrCode = new QrCode(
            data: $text,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::Low,
            size: 300,
            margin: 10,
            roundBlockSizeMode: RoundBlockSizeMode::Margin,
            foregroundColor: new Color(0, 0, 0),
            backgroundColor: new Color(255, 255, 255)
        );

        $label = new Label(
            text: 'Label',
            textColor: new Color(255, 0, 0)
        );

        $result = $writer->write($qrCode, null, $label);

        return $result->getDataUri();
    }

    /**
     * Owner
     *
     * @return Relationship
     */
    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * Invoice
     *
     * @return Relationship
     */
    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function end_result()
    {
        return $this->belongsTo(CollectionEndResult::class, 'result_id');
    }

    public function packaging()
    {
        return $this->belongsTo(PackagingCategory::class, 'pack_id');
    }

    /**
     * Laboratory Codes
     *
     * @return Relationship
     */
    public function code()
    {
        return $this->hasOne(LabCode::class, 'collection_id');
    }

    /**
     * Samples
     *
     * @return Relationship
     */
    public function samples()
    {
        return $this->hasManyThrough(Sample::class, LabCode::class, 'collection_id', 'cl_id');
    }

    public function sampleEntry(): HasOne
    {
        return $this->hasOne(VAPSampleEntry::class, 'collection_product_id');
    }

    /**
     * Customer
     *
     * @return Relationship
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    /**
     * Vehicle
     *
     * @return Relationship
     */
    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class, 'vehicle_id');
    }

    /**
     * Warehouse
     *
     * @return Relationship
     */
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    /**
     * Quality Certificate
     *
     * @return Relationship
     */
    public function quality_certificate()
    {
        return $this->hasOne(QualityCertificate::class, 'collection_id');
    }

    /**
     * Temperature
     *
     * @return Relationship
     */
    public function temperature()
    {
        return $this->belongsTo(Temperature::class, 'temperature_id');
    }

    public function scopeArchived($query)
    {

        return $query->whereHas('quality_certificate', function ($query) {
            $query->whereNull('deleted_at');
        });
    }

    public function scopePending($query)
    {

        return $query->whereDoesntHave('quality_certificate');
    }

    public function recollection()
    {
        return $this->hasOne(Recollection::class, 'collection_id');
    }

    public function invoice_item()
    {
        return $this->morphOne(InvoiceItem::class, 'itemable');
    }

    public function quote_item()
    {
        return $this->morphOne(QuoteItem::class, 'itemable');
    }

    public function credit_note_item()
    {
        return $this->morphOne(CreditNoteItem::class, 'itemable');
    }

    public function proposal_item()
    {
        return $this->morphOne(ProposalItem::class, 'itemable');
    }

    public static function boot()
    {
        parent::boot();
        self::deleting(function ($product) {
            if ($product->collection->products && $product->collection->products->count() < 2) {
                $product->collection->collectionable->delete();
                $product->collection()->delete();
            }

            $product->quality_certificate()->delete();
            $product->code->samples->each->delete();
        });
    }

    public static function getAllowedFilters(): array
    {
        return [
            AllowedFilter::partial('code.code'),
            AllowedFilter::partial('customer.name'),
            AllowedFilter::partial('product.name'),
            AllowedFilter::partial('bl'),
            AllowedFilter::partial('lot'),
            AllowedFilter::partial('comercial_brand'),
            AllowedFilter::partial('collection_date'),
            AllowedFilter::partial('qty'),
            AllowedFilter::custom('globalFilter', new GlobalFilter(['lot'])),
            AllowedFilter::trashed(),
        ];
    }

    public static function getAllowedSorts(): array
    {
        return [
            'qty',
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
                'filter_field' => 'code.code',
                'filterable' => true,
                'type' => 'string',
                'format' => '',
                'filter' => '',
            ],
            [
                'name' => trans('gestlab.general.labels.analysis.sample_entry'),
                'value' => 'entry_lineage',
                'filter_field' => 'sampleEntry.code',
                'filterable' => false,
                'type' => 'string',
                'format' => '',
                'filter' => '',
            ],
            [
                'name' => trans('gestlab.general.labels.status'),
                'value' => 'tracking_label',
                'filter_field' => 'tracking_label',
                'filterable' => false,
                'type' => 'string',
                'format' => '',
                'filter' => '',
            ],
            [
                'name' => trans('gestlab.general.labels.direct_collections.product_id'),
                'value' => 'product',
                'filter_field' => 'product.name',
                'filterable' => false,
                'type' => 'string',
                'format' => '',
                'filter' => '',
                'options' => [],
                'config' => [
                    'url' => route('products.getProduct'),
                    'label' => 'name',
                    'value' => 'id',
                ],
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
                ],
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
            ['value' => 'with', 'text' => trans('gestlab.general.labels.trashed_with')],
        ];
    }
}
