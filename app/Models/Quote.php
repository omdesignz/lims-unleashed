<?php

namespace App\Models;

use App\Models\Concerns\BelongsToFinancialLaboratory;
use App\Models\Concerns\HasDocumentRevisions;
use App\Traits\HasScopedSequence;
use Illuminate\Database\Eloquent\Casts\AsCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Artisan;

class Quote extends Model
{
    use BelongsToFinancialLaboratory;
    use HasDocumentRevisions, HasFactory, HasScopedSequence, SoftDeletes;

    public const MENU_NAME = 'quotes';

    private ?array $authoringIdentity = null;

    public function generatedAuthoringIdentity(): ?array
    {
        return $this->authoringIdentity;
    }

    protected $attributes = ['is_service' => false];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'warehouse_id',
        'discount_type',
        'customer_id',
        'quote_no',
        'quote_month',
        'seq',
        'description',
        'internal_ref',
        'file_path',
        'due_date',
        'date',
        'discount',
        'sub_total',
        'total',
        'obs',
        'tax',
        'unique_hash',
        'status',
        'is_original',
        'use_matrix_price',
        'exported_saft',
        'converted_to_invoice',
        'extra_data',
        'is_service',
        'invoice_id',
    ];

    protected $table = 'quotes';

    protected $dates = ['created_at', 'updated_at', 'deleted_at', 'date'];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'status' => 'boolean',
        'is_original' => 'boolean',
        'use_matrix_price' => 'boolean',
        'is_service' => 'boolean',
        'exported_saft' => 'boolean',
        'converted_to_invoice' => 'boolean',
        'extra_data' => AsCollection::class,
    ];

    public function sequence(): array
    {
        return [
            'group' => 'quote_month',
            'fieldName' => 'seq',
        ];
    }

    /**
     * Quote Items
     *
     * @return Relationship
     */
    public function items()
    {
        return $this->hasMany(QuoteItem::class);
    }

    /**
     * User
     *
     * @return Relationship
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Warehouse
     *
     * @return Relationship
     */
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Customer
     *
     * @return Relationship
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class);
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

    /**
     * Discount Category
     *
     * @return Relationship
     */
    public function discount_category()
    {
        return $this->belongsTo(DiscountCategory::class, 'discount_type');
    }

    public static function boot()
    {
        parent::boot();

        static::saving(function ($quote) {});

        static::creating(function (Quote $quote) {
            $quote->quote_no = 'PP '.$quote->quote_month.'/'.$quote->seq;
            $quote->authoringIdentity = ['quote_month' => $quote->quote_month, 'seq' => $quote->seq, 'quote_no' => $quote->quote_no];
        });

        static::created(function (Quote $quote) {
            if (filled($quote->unique_hash)) {
                throw new \LogicException('New quotes cannot supply a pre-signed identity.');
            }
            Artisan::call('app:sign-quote-with-hash', ['quote' => $quote->id]);
            $quote->authoringIdentity['unique_hash'] = $quote->fresh()->unique_hash;
        });

    }
}
