<?php

namespace App\Models;

use App\Traits\EagerLoadPivotTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class Parameter extends Model
{
    use HasFactory, SoftDeletes, EagerLoadPivotTrait;

    public CONST MENU_NAME = 'parameters';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'code',
        'description',
        'price',
        'charge_tax',
        'withhold_tax',
        'active',
        'exemption_id',
        'exemption_code',
        'tax_id',
        'tax_percentage',
        'optimal_analysis_time',
        'result_is_qualitative',
        'formula_expression',
        'calculation_parameters',
        'decimal_places',
        'result_type',
        'formula_id',
        'variables',
        'requires_calculation',
    ];

    protected $table = 'parameters';
    protected $dates = ['created_at', 'updated_at', 'deleted_at'];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'charge_tax' => 'boolean',
        'withhold_tax' => 'boolean',
        'active' => 'boolean',
        'calculation_parameters' => 'array',
        'variables' => 'array',
        'requires_calculation' => 'boolean',
        'result_is_qualitative' => 'boolean',
    ];



    public function profiles()
    {
        return $this->belongsToMany(Profile::class, 'parameter_profile')->withPivot('category_id', 'category_label', 'unit_id', 'unit_label', 'standard_id', 'standard_label', 'formula_id', 'formula_label', 'protocol_id', 'protocol_label', 'nwp_id', 'nwp_label', 'min_ref_value', 'max_ref_value', 'dilutions', 'extra_data', 'optimal_analysis_time', 'count', 'ref_val_origin')
                                                                   ->using(ParameterProfile::class);
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

    /**
     * Tax Exemption
     *
     * @return Relationship
     */
    public function exemption() {
        return $this->belongsTo(TaxExemption::class, 'exemption_id');
    }

    /**
     * Tax Category
     *
     * @return Relationship
     */
    public function tax_category() {
        return $this->belongsTo(TaxType::class, 'tax_id');
    }

    public function parameter_profiles()
    {
        return $this->hasMany(ParameterProfile::class);
    }

    public function formula()
    {
        return $this->belongsTo(Formula::class);
    }

    public function results()
    {
        return $this->hasMany(Result::class);
    }
    
    public function getCalculationRequirements(): array
    {
        if (!$this->requires_calculation) {
            return [];
        }

        if ($this->formula) {
            return array_keys($this->formula->variables ?? []);
        }

        // Extract variables from formula expression
        preg_match_all('/\{([^}]+)\}/', $this->formula_expression ?? '', $matches);
        return $matches[1] ?? [];
    }

    public function canCalculate(array $availableValues): bool
    {
        $required = $this->getCalculationRequirements();
        return empty(array_diff($required, array_keys($availableValues)));
    }

    // Automatically serialize calculation_parameters when saving
    public function setCalculationParametersAttribute($value)
    {
        $this->attributes['calculation_parameters'] = is_array($value) 
            ? json_encode($value) 
            : $value;
    }

    // Automatically deserialize when accessing
    public function getCalculationParametersAttribute($value)
    {
        if (is_array($value)) {
            return $value;
        }
        
        return $value ? json_decode($value, true) : [];
    }

}
