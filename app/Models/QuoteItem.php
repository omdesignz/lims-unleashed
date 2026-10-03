<?php

namespace App\Models;

use App\Models\Concerns\BelongsToFinancialLaboratory;
use App\Services\LaboratoryWorkflowOwnership;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\AsCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\ValidationException;

class QuoteItem extends Model
{
    use BelongsToFinancialLaboratory;
    use HasFactory, SoftDeletes;

    public const MENU_NAME = null;

    public function assertOperationalSourceOwnership(): void
    {
        if ($this->itemable_id === null) {
            return;
        }
        $quote = Quote::query()->findOrFail($this->quote_id);
        $ownership = app(LaboratoryWorkflowOwnership::class);
        $query = match ($this->itemable_type) {
            'collectionproduct' => $ownership->collectionProductsForLaboratory((int) $quote->lab_id)
                ->where('customer_id', $quote->customer_id)->where('warehouse_id', $quote->warehouse_id),
            'labcode' => $ownership->labCodesForLaboratory((int) $quote->lab_id)
                ->whereHas('collection', fn (Builder $query): Builder => $query
                    ->where('customer_id', $quote->customer_id)->where('warehouse_id', $quote->warehouse_id)),
            default => null,
        };
        if (! $query || ! $query->whereKey($this->itemable_id)->lockForUpdate()->exists()) {
            throw ValidationException::withMessages(['items' => 'A origem do item deve pertencer ao laboratório, cliente e local da cotação.']);
        }
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'quote_id',
        'itemable_id',
        'itemable_type',
        'unit_id',
        'exemption_id',
        'exemption_code', // Added
        'discount_id', // Added
        'item_id',
        'item_description',
        'qty',
        'unit_price',
        'total',
        'discount_percentage',
        'discount_amount',
        'tax_id', // Added
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
     * Quote
     *
     * @return Relationship
     */
    public function quote()
    {
        return $this->belongsTo(Quote::class, 'quote_id');
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
