<?php

namespace App\Models;

use App\Models\Concerns\HasDocumentRevisions;
use HighSolutions\EloquentSequence\Sequence;
use Illuminate\Database\Eloquent\Casts\AsCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Artisan;

class Receipt extends Model
{
    use HasFactory, SoftDeletes, Sequence, HasDocumentRevisions;

    public const MENU_NAME = 'receipts';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'invoice_id',
        'customer_id',
        'warehouse_id',
        'payment_type',
        'rec_no',
        'rec_month',
        'seq',
        'description',
        'date',
        'obs',
        'unique_hash',
        'exported_saft',
        'is_original',
        'file_path',
        'extra_data',
    ];

    protected $table = 'receipts';
    protected $dates = ['created_at', 'updated_at', 'deleted_at', 'date'];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'exported_saft' => 'boolean',
        'is_original' => 'boolean',
        'extra_data' => AsCollection::class,
    ];

    public function sequence()
    {
        return [
            'group' => 'rec_month',
            'fieldName' => 'seq',
            // 'orderFrom1' => true,
            'notUpdateOnDelete' => true
        ];
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
     * Get the user associated with the Receipt.
     */
    public function items()
    {
        return $this->hasMany(InvoiceReceipt::class);
    }

    /**
     * Invoice
     *
     * @return Relationship
     */
    public function invoice()
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }

    /**
     * Payment Type
     *
     * @return Relationship
     */
    public function type()
    {
        return $this->belongsTo(PaymentCategory::class, 'payment_type');
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
     * Warehouse
     *
     * @return Relationship
     */
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }


    public static function boot()
    {
        parent::boot();

        static::creating(function ($receipt) {

            $receipt->rec_no = 'RG ' . $receipt->rec_month . '/' . $receipt->seq;
        });

        static::created(function ($receipt) {
            Artisan::call('app:sign-receipt-with-hash', ['receipt' => $receipt->id]);
        });
    }
}
