<?php

namespace App\Models;

use App\Models\Concerns\HasDocumentRevisions;
use HighSolutions\EloquentSequence\Sequence;
use Illuminate\Database\Eloquent\Casts\AsCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Artisan;

class Invoice extends Model
{
    use HasFactory, SoftDeletes, Sequence, HasDocumentRevisions;

    public const MENU_NAME = 'invoices';

    public const STATUS_CODE_NORMAL = 'N';
    public const STATUS_CODE_CANCELED = 'A';


    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'type_id',
        'user_id',
        'customer_id',
        'discount_type',
        'warehouse_id',
        'inv_no',
        'invoice_month',
        'seq',
        'description',
        'internal_ref',
        'file_path',
        'date',
        'paid_date',
        'due_date',
        'payment_method',
        'discount',
        'tax',
        'sub_total',
        'total',
        'amount_due',
        'obs',
        'unique_hash',
        'status_code',
        'status',
        'is_original',
        'use_matrix_price',
        'exported_saft',
        'invoiceable_type',
        'invoiceable_id',
        'extra_data',
        'is_service',
    ];

    protected $table = 'invoices';
    protected $dates = ['created_at', 'updated_at', 'deleted_at', 'date', 'paid_date', 'due_date'];


    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'status' => 'boolean',
        'use_matrix_price' => 'boolean',
        'is_service' => 'boolean',
        'is_original' => 'boolean',
        'exported_saft' => 'boolean',
        'extra_data' => AsCollection::class,
    ];

    public function sequence()
    {
        return [
            'group' => ['invoice_month', 'type_id'],
            'fieldName' => 'seq',
            'notUpdateOnDelete' => true,
        ];
    }

    /**
     * Invoice Items
     *
     * @return Relationship
     */
    public function items()
    {
        return $this->hasMany(InvoiceItem::class);
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
     * Armazém
     *
     * @return Relationship
     */
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
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

    /**
     * Invoice Category
     *
     * @return Relationship
     */
    public function invoice_category()
    {
        return $this->belongsTo(InvoiceCategory::class, 'type_id');
    }

    /**
     * Issued By
     *
     * @return Relationship
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Credit Note
     *
     * @return Relationship
     */
    public function credit_note()
    {
        return $this->hasOne(CreditNote::class);
    }


    /**
     * Invoiceable
     *
     * @return Relationship
     */
    public function invoiceable()
    {
        return $this->morphTo()->withTrashed();
    }


    /**
     * Receipt
     *
     * @return Relationship
     */
    public function receipt()
    {
        return $this->belongsToMany(Receipt::class)->withPivot('paid_amount', 'invoice_pending_amount', 'pending_amount');
    }

    /**
     * Unpaid Invoices
     *
     * @return Relationship
     */
    public function scopeUnpaid($query, $warehouse)
    {
        return $query->where('status', false)
            ->where('warehouse_id', $warehouse)
            ->where('amount_due', '>', 0)
            ->where('status_code', self::STATUS_CODE_NORMAL);
    }

    /**
     * Unpaid Invoices
     *
     * @return Relationship
     */
    public function scopepaid($query, $warehouse)
    {
        return $query->where('status', true)
            ->where('warehouse_id', $warehouse)
            ->where('amount_due', '=', 0)
            ->where('status_code', self::STATUS_CODE_NORMAL);
    }


    public static function boot()
    {
        parent::boot();

        static::creating(function ($invoice) {

            $type = InvoiceCategory::findOrFail($invoice->type_id);

            $invoice->inv_no = $type->code . ' ' . $invoice->invoice_month . '/' . $invoice->seq;
        });

        static::created(function ($invoice) {

            // Sign Invoice With Unique Hash After Creation
            Artisan::call('app:sign-invoice-with-hash', ['invoice' => $invoice->id]);

            if ($invoice->invoice_category->code == 'FR') {
                $invoice->update(['status' => true]);
            }
        });
    }
}
