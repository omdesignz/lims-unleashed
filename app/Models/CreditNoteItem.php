<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\AsCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CreditNoteItem extends Model
{
    use HasFactory, SoftDeletes;

    public const MENU_NAME = null;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'note_id',
        'itemable_id',
        'itemable_type',
        'unit_id',
        'exemption_id',
        'exemption_code',
        'discount_id',
        'tax_id',
        'item_id',
        'item_description',
        'qty',
        'unit_price',
        'total',
        'discount_percentage',
        'discount_amount',
        'tax_amount',
        'tax_percentage',
        'obs',
        'charge_tax',
        'extra_data',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'charge_tax' => 'boolean',
        'extra_data' => AsCollection::class,
    ];


    /**
     * Credit Note
     *
     * @return Relationship
     */
    public function credit_note()
    {
        return $this->belongsTo(CreditNote::class, 'note_id');
    }


    /**
     * Unit
     *
     * @return Relationship
     */
    public function unit()
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }


    /**
     * Tax Exemption
     *
     * @return Relationship
     */
    public function exemption()
    {
        return $this->belongsTo(TaxExemption::class, 'exemption_id');
    }

    public function itemable()
    {
        return $this->morphTo();
    }
}
