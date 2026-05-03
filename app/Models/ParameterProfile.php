<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Casts\AsCollection;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\SoftDeletes;

class ParameterProfile extends Pivot
{
    use HasFactory;

    public $incrementing = true;

    public const MENU_NAME = null;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'unit_label',
        'protocol_label',
        'standard_label',
        'nwp_label',
        'count',
        'min_ref_value',
        'max_ref_value',
        'dilutions',
        'parameter_id',
        'profile_id',
        'category_id',
        'formula_id',
        'formula_label',
        'category_label',
        'unit_id',
        'nwp_id',
        'protocol_id',
        'standard_id',
        'extra_data',
        'optimal_analysis_time',
        'ref_val_origin'

    ];

    protected $table = 'parameter_profile';
    protected $dates = ['created_at', 'updated_at', 'deleted_at'];


    protected function casts(): array
    {
        return [
            // 'count' => 'boolean',
            // 'extra_data' => AsCollection::class,
        ];
    }

    // protected function extra_data(): Attribute
    // {
    //     return Attribute::make(
    //         get: fn ($value) => json_decode($value ?? '[]', true),
    //         set: fn ($value) => json_encode($value ?? []),
    //     );
    // }


    /**
     * Parameter
     *
     * @return Relationship
     */
    public function parameter()
    {
        return $this->belongsTo(Parameter::class, 'parameter_id')->withDefault();
    }


    /**
     * Profile
     *
     * @return Relationship
     */
    public function profile()
    {
        return $this->belongsTo(Profile::class, 'profile_id')->withDefault();
    }

    /**
     * Result Category
     *
     * @return Relationship
     */
    public function category()
    {
        return $this->belongsTo(ResultCategory::class, 'category_id')->withDefault();
    }

    /**
     * Formula
     *
     * @return Relationship
     */
    public function formula()
    {
        return $this->belongsTo(Formula::class, 'formula_id')->withDefault();
    }

    /**
     * Unit
     *
     * @return Relationship
     */
    public function unit()
    {
        return $this->belongsTo(Unit::class, 'unit_id')->withDefault();
    }


    /**
     * Protocol
     *
     * @return Relationship
     */
    public function protocol()
    {
        return $this->belongsTo(Protocol::class, 'protocol_id')->withDefault();
    }


    /**
     * Standard
     *
     * @return Relationship
     */
    public function standard()
    {
        return $this->belongsTo(Standard::class, 'standard_id')->withDefault();
    }


    /**
     * Normative Work Procedure
     *
     * @return Relationship
     */
    public function nwp()
    {
        return $this->belongsTo(NormativeWorkProcedure::class, 'nwp_id')->withDefault();
    }
}
