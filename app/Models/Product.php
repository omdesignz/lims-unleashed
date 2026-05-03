<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class Product extends Model
{
    use HasFactory, SoftDeletes;

    public CONST MENU_NAME = 'products';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'description',
        'matrix_id',
        'exemption_id',
        'exemption_code',
        'charge_tax',
        'withhold_tax',
        'price',
        'fixed_price',
        'tax_id',
        'tax_percentage'
    ];

    protected $table = 'products';
    protected $dates = ['created_at', 'updated_at', 'deleted_at'];


    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'charge_tax' => 'boolean',
        'withhold_tax' => 'boolean',
    ];

    /**
     * Tax Exemption
     *
     * @return Relationship
     */
    public function exemption() {
        return $this->belongsTo(TaxExemption::class, 'exemption_id');
    }

    public function matrix()
    {
        return $this->belongsTo(Matrix::class);
    }

     /**
     * Tax Category
     *
     * @return Relationship
     */
    public function tax_category() {
        return $this->belongsTo(TaxType::class, 'tax_id');
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


}
