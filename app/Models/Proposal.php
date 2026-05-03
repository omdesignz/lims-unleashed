<?php

namespace App\Models;

use App\Enums\Proposals\ProposalTrackingStatus;
use App\Models\Concerns\HasDocumentRevisions;
use HighSolutions\EloquentSequence\Sequence;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Artisan;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;
use App\Filters\GlobalFilter;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\Label\Label;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\SvgWriter;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Proposal extends Model
{
    use HasFactory, SoftDeletes, Sequence, HasDocumentRevisions;
    //
    public const MENU_NAME = 'proposals';

    protected $table = 'proposals';
    protected $dates = ['created_at', 'updated_at', 'deleted_at', 'expiry_date']; 
    
    protected $fillable = [
        'seq',
        'proposal_year',
        'proposal_no',
        'service_location',
        'customer_id',
        'warehouse_id',
        'department_id',
        'template_id',
        'status',
        'details',
        'expiry_date',
        'is_original',
        'discount_type',
        'file_path',
        'tolerance_days',
        'sub_total',
        'total',
        'user_id',
        'unique_hash',
        'use_matrix_price',
        'withholding_tax_amount',
        'withholding_tax_percentage',
        'global_discount_amount',
        'global_discount_percentage',
        'withhold_tax',
        'converted_to_invoice',
        'obs',

    ];

    public $casts = [
        'status' => ProposalTrackingStatus::class,
        'converted_to_invoice' => 'boolean',
        'details' => 'array',
        'use_matrix_price' => 'boolean', 
        'withhold_tax' => 'boolean',
    ];

    public function sequence()
    {
        return [
            'group' => 'proposal_year',
            'fieldName' => 'seq',
        ];
    }

    public function renderContent()
    {
        $placeholders = [
            '{{customer_id}}' => $this->customer->name,
            '{{proposal_no}}' => $this->proposal_no,
            '{{proposal_date}}' => $this->created_at->format('Y-m-d'),
            '{{total}}' => $this->total
        ];

        return strtr($this->template->content, $placeholders);
    }

    public function getQrAttribute()
    {
        $writer = new SvgWriter();

        $text = <<< EOT
                    {$this->proposal_no}
                    {$this->id}
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

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function discount_category(): BelongsTo
    {
        return $this->belongsTo(DiscountCategory::class);
    }

    public function template()
    {
        return $this->belongsTo(ProposalTemplate::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ProposalItem::class, 'proposal_id');
    }

    public function scopeAccepted($query)
    {
        return $query->where('status', ProposalTrackingStatus::ACCEPTED->value);
    }

    public function isAccepted(): bool
    {
        $status = $this->status instanceof ProposalTrackingStatus
            ? $this->status
            : ProposalTrackingStatus::tryFrom((string) $this->status);

        return $status === ProposalTrackingStatus::ACCEPTED;
    }

    public function getMorphClass()
    {
        return 'proposal';
    }

    public static function boot()
    {
        parent::boot();

        static::saving(function ($proposal) {

        });

        static::creating(function (Proposal $proposal) {
            $proposal->proposal_no = 'PROP ' . Department::find($proposal->department_id)->name . '/' . $proposal->seq . '/' . $proposal->proposal_year;

            $proposal->details = json_encode(ProposalTemplate::find($proposal->template_id)->content);
        });

        static::created(function ($proposal) {
            Artisan::call('app:sign-proposal-with-hash', ['proposal' => $proposal->id]);
        });

        self::deleting(function ($proposal) {

        });
    }

    public function complianceAgreement()
    {
        return $this->hasOne(ProposalComplianceAgreement::class);
    }

    public function complianceAgreementLogs()
    {
        return $this->hasMany(ProposalComplianceAgreementLog::class);
    }

    public static function getAllowedFilters(): array
    {
        return [
            AllowedFilter::partial('proposal_no'),
            AllowedFilter::partial('customer_id'),
            AllowedFilter::partial('service_location'),
            AllowedFilter::partial('created_at'),
            AllowedFilter::custom('globalFilter', new GlobalFilter(['proposal_no', 'service_location'])),
            AllowedFilter::trashed(),
        ];
    }

    public static function getAllowedSorts(): array
    {
        return [
            'proposal_no',
            'created_at',
        ];
    }

    public static function getColumns(): array
    {
        return [
                [
                    'name' => trans('gestlab.general.labels.proposals.proposal_no'),
                    'value' => 'proposal_no',
                    'filter_field' => 'proposal_no',
                    'filterable' => true,
                    'type' => 'string',
                    'format' => '',
                    'filter' => '',
                ],
                [
                    'name' => trans('gestlab.general.labels.proposals.customer_id'),
                    'value' => 'customer',
                    'filter_field' => 'customer_id',
                    'filterable' => true,
                    'type' => 'remote_select',
                    'format' => '',
                    'filter' => '',
                    'options' => [
                    ],
                    'config' => [
                        'url' => route('customers.getCustomer'),
                        'label' => 'name',
                        'value' => 'id',
                    ]
                ],
                [
                    'name' => trans('gestlab.general.labels.proposals.service_location'),
                    'value' => 'service_location',
                    'filter_field' => 'service_location',
                    'filterable' => true,
                    'type' => 'string',
                    'format' => '',
                    'filter' => '',
                ],
                [
                    'name' => trans('gestlab.general.labels.proposals.service_location'),
                    'value' => 'service_location',
                    'filter_field' => 'service_location',
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
