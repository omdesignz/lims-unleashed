<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class VAPProposalItem extends Model
{
    use SoftDeletes;

    protected $table = 'proposal_items';

    protected $fillable = [
        'item_id',
        'item_description',
        'proposal_id',
        'exemption_id',
        'standard_id',
        'exemption_code',
        'discount_id',
        'unit_id',
        'tax_id',
        'qty',
        'unit_price',
        'total',
        'discount_percentage',
        'discount_amount',
        'tax_amount',
        'tax_percentage',
        'obs',
        'charge_tax',
        'withhold_tax',
        'global_discount_amount',
        'global_discount_portion_percentage',
        'itemable_type',
        'itemable_id',
        'extra_data',
    ];

    protected $casts = [
        'qty' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'total' => 'decimal:2',
        'discount_percentage' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'tax_percentage' => 'decimal:2',
        'charge_tax' => 'boolean',
        'withhold_tax' => 'boolean',
        'global_discount_amount' => 'decimal:2',
        'global_discount_portion_percentage' => 'decimal:2',
        'extra_data' => 'array',
        'deleted_at' => 'datetime',
    ];

    // Relationships
    public function proposal(): BelongsTo
    {
        return $this->belongsTo(VAPProposal::class, 'proposal_id');
    }

    public function exemption(): BelongsTo
    {
        return $this->belongsTo(TaxExemption::class);
    }

    public function standard(): BelongsTo
    {
        return $this->belongsTo(Standard::class);
    }

    public function discount(): BelongsTo
    {
        return $this->belongsTo(DiscountCategory::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function tax(): BelongsTo
    {
        return $this->belongsTo(TaxType::class);
    }

    public function itemable(): MorphTo
    {
        return $this->morphTo();
    }
}