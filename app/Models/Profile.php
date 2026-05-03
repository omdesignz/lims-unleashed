<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\AsCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Profile extends Model
{
    use HasFactory, SoftDeletes;

    public CONST MENU_NAME = 'profiles';

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
        'category_id'
    ];

    protected $table = 'profiles';
    protected $dates = ['created_at', 'updated_at', 'deleted_at'];

    protected function casts(): array
    {
        return [
            'parameter_profile.count' => 'boolean',
            'parameter_profile.extra_data' => AsCollection::class,
        ];
    }

    public function type()
    {
        return $this->belongsTo(AnalysisCategory::class, 'category_id');
    }

    public function items()
    {
        return $this->hasMany(ParameterProfile::class);
    }

    // public function getPriceBasedOnParametersAttribute()
    // {
    //     $parameterIDs = collect($this->parameters)->where('active', true)->pluck('id')->toArray();

    //     $total_price = Parameter::whereIn('id', $parameterIDs)->sum('price');

    //     return $total_price ?? 0;
    // }

    public function getPriceBasedOnParametersAttribute()
    {
        // Check if the parameters relationship is already loaded to avoid a new query
        if ($this->relationLoaded('parameters')) {
            return $this->parameters
                ->where('active', true)
                ->sum('price');
        }

        // Fallback if not loaded (still triggers a query, but cleaner)
        return Parameter::whereIn('id', collect($this->parameters)->pluck('id'))
            ->where('active', 1)
            ->sum('price') ?? 0;
    }

    public function parameter_profiles()
    {
        return $this->hasMany(ParameterProfile::class);
    }

    /**
     * Parameters
     *
     * @return Relationship
     */
    public function parameters()
    {
        return $this->belongsToMany(Parameter::class, 'parameter_profile')->withPivot('category_id', 'category_label', 'unit_id', 'unit_label', 'standard_id', 'standard_label', 'formula_id', 'formula_label', 'protocol_id', 'protocol_label', 'nwp_id', 'nwp_label', 'min_ref_value', 'max_ref_value', 'dilutions', 'extra_data', 'optimal_analysis_time', 'count', 'ref_val_origin')
                                                                          ->using(ParameterProfile::class);
    }

    /**
     * Matrixes
     *
     * @return Relationship
     */
     public function matrixes()
     {
         return $this->belongsToMany(Matrix::class)->withPivot('matrix_id', 'matrix', 'profile_id', 'profile')->withTimestamps();
     }
}