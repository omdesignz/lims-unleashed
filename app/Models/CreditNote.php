<?php

namespace App\Models;

use App\Models\Concerns\HasDocumentRevisions;
use HighSolutions\EloquentSequence\Sequence;
use Illuminate\Database\Eloquent\Casts\AsCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Artisan;

class CreditNote extends Model
{
    use HasFactory, SoftDeletes, Sequence, HasDocumentRevisions;

    public const MENU_NAME = 'credit_notes';

    public const REASON_RECTIFICATION = 'R';
    public const REASON_CANCELATION = 'A';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'customer_id',
        'warehouse_id',
        'invoice_id',
        'discount_type',
        'note_no',
        'note_month',
        'seq',
        'reason',
        'internal_ref',
        'file_path',
        'date',
        'paid_date',
        'sub_total',
        'total',
        'amount',
        'obs',
        'unique_hash',
        'status',
        'is_original',
        'use_matrix_price',
        'exported_saft',
        'collection_id',
        'extra_data',
        'is_service',
    ];

    protected $table = 'credit_notes';
    protected $dates = ['created_at', 'updated_at', 'deleted_at', 'date'];

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
            'group' => 'note_month',
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
        return $this->hasMany(CreditNoteItem::class, 'note_id');
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
     * Invoice
     *
     * @return Relationship
     */
    public function invoice()
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
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


    public static function boot()
    {
        parent::boot();

        static::creating(function ($note) {

            $note->note_no = 'NC ' . $note->note_month . '/' . $note->seq;
        });

        static::created(function ($note) {

            // dd($note);
            Artisan::call('app:sign-credit-note-with-hash', ['credit_note' => $note->id]);

            if (!is_null($note->invoice_id)) {
                $invoice = Invoice::findOrFail($note->invoice_id);

                // Subtract Credit Note Total from invoice Due Amount
                $invoice->update([
                    'amount_due' => $invoice->amount_due - $note->total
                ]);
            }
        });
    }
}
