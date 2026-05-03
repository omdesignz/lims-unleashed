<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\AsCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProposalItem extends Model
{
    //
    protected $table = 'proposal_items';

    protected $fillable = [
        'proposal_id',
        'itemable_id',
        'itemable_type',
        'unit_id',
        'standard_id',
        'exemption_id',
        'exemption_code', # Added
        'discount_id', # Added
        'item_id',
        'item_description',
        'qty',
        'unit_price',
        'total',
        'discount_percentage',
        'discount_amount',
        'tax_id', # Added
        'tax_amount',
        'tax_percentage',
        'obs',
        'charge_tax',
        'extra_data',
        'withhold_tax',
        'global_discount_amount',
        'global_discount_portion_percentage',
    ];

        /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'charge_tax' => 'boolean',
        'withhold_tax' => 'boolean',
        'extra_data' => AsCollection::class,
    ];

    public function proposal(): BelongsTo
    {
        return $this->belongsTo(Proposal::class, 'proposal_id');
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

    public function standard():BelongsTo
    {
        return $this->belongsTo(Standard::class, 'standard_id');
    }


    public function itemable()
    {
        return $this->morphTo();
    }
}
