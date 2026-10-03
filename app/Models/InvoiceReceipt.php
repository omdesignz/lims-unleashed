<?php

namespace App\Models;

use App\Models\Concerns\BelongsToFinancialLaboratory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InvoiceReceipt extends Model
{
    use BelongsToFinancialLaboratory, HasFactory, SoftDeletes;

    public const MENU_NAME = null;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'invoice_id',
        'receipt_id',
        'payment_id',
        'paid_amount',
        'pending_amount',
        'invoice_pending_amount',
        'obs',
    ];

    protected $table = 'invoice_receipt';

    protected $dates = ['created_at', 'updated_at', 'deleted_at'];

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
     * Invoice
     *
     * @return Relationship
     */
    public function invoice()
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }

    /**
     * Receipt
     *
     * @return Relationship
     */
    public function receipt()
    {
        return $this->belongsTo(Receipt::class, 'receipt_id');
    }

    /**
     * Payment
     *
     * @return Relationship
     */
    public function payment_category()
    {
        return $this->belongsTo(PaymentCategory::class, 'payment_id');
    }
}
