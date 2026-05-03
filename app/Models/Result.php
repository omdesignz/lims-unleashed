<?php

namespace App\Models;

use App\Traits\ISO17025Revisionable;
use Illuminate\Database\Eloquent\Casts\AsCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Activitylog\Traits\CausesActivity;

class Result extends Model implements HasMedia
{
    use HasFactory, SoftDeletes, ISO17025Revisionable, CausesActivity, InteractsWithMedia;

    public const MENU_NAME = 'results';
    public const ABILITIES = ['view', 'add', 'edit', 'delete', 'restore', 'insert', 'verify', 'approve'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'sample_id',
        'product_id',
        'parameter_id',
        'code_id',
        'profile_id',
        'matrix_id',
        'collection_id',
        'inserted_by_id',
        'verified_by_id',
        'approved_by_id',
        'type_id',
        'is_calculated',
        'is_override',
        'status',
        'count',
        'requested_counter_analysis',
        'inserted_by',
        'verified_by',
        'verification_status',
        'approved_by',
        'inserted_value',
        'insertion_notes',
        'verified_value',
        'verification_notes',
        'approved_value',
        'approval_notes',
        'resultable_id',
        'resultable_type',
        'inserted_date',
        'verified_date',
        'approved_date',
        'calculation_metadata',
        'extra_data',
        'min_ref_value',
        'max_ref_value',
        'ref_val_origin',
        'unit_id',
        'nwp_id',
        'protocol_id',
        'standard_id',
        'unit_label',
        'parameter_label',
        'protocol_label',
        'nwp_label',
        'code_label',
        'product_label',
        'standard_label',
        'category_label',
        'uncertainty_value',
        'sumC',
        'volume',
        'n1',
        'n2',
        'dilution',
        'd1',
        'd2',
        'cfu1',
        'cfu2',
        'calculated_at',
    ];

    protected $table = 'results';
    protected $dates = ['created_at', 'updated_at', 'deleted_at'];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'status' => 'boolean',
        'count' => 'boolean',
        'requested_counter_analysis' => 'boolean',
        'extra_data' => AsCollection::class,
        'calculation_metadata' => 'array',
        'is_calculated' => 'boolean',
        'is_override' => 'boolean',
    ];


    /**
     * Collection
     *
     * @return Relationship
     */
    public function collection()
    {
        return $this->belongsTo(CollectionProduct::class, 'collection_id')->withTrashed();
    }


    /**
     * Parameter
     *
     * @return Relationship
     */
    public function parameter()
    {
        return $this->belongsTo(Parameter::class, 'parameter_id')->withTrashed();
    }


    /**
     * Profile
     *
     * @return Relationship
     */
    public function profile()
    {
        return $this->belongsTo(Profile::class, 'profile_id')->withTrashed();
    }


    /**
     * Matrix
     *
     * @return Relationship
     */
    public function matrix()
    {
        return $this->belongsTo(Matrix::class, 'matrix_id')->withTrashed();
    }

    /**
     * Product
     *
     * @return Relationship
     */
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id')->withTrashed();
    }

    /**
     * Inserted By
     *
     * @return Relationship
     */
    public function inserted_by()
    {
        return $this->belongsTo(User::class, 'inserted_by_id')->withTrashed();
    }

    /**
     * Verified By
     *
     * @return Relationship
     */
    public function verified_by()
    {
        return $this->belongsTo(User::class, 'verified_by_id')->withTrashed();
    }

    /**
     * Verified By Signature
     *
     * @return Relationship
     */
    public function verified_signature()
    {
        return $this->belongsTo(User::class, 'verified_by_id')->withTrashed();
    }

    /**
     * Approved By
     *
     * @return Relationship
     */
    public function approved_by()
    {
        return $this->belongsTo(User::class, 'approved_by_id')->withTrashed();
    }

    /**
     * Approved By Signature
     *
     * @return Relationship
     */
    public function approved_signature()
    {
        return $this->belongsTo(User::class, 'approved_by_id')->withTrashed();
    }

    /**
     * Sample
     *
     * @return Relationship
     */
    public function sample()
    {
        return $this->belongsTo(Sample::class, 'sample_id');
    }

    public function counter_analysis()
    {
        return $this->hasOne(CounterAnalysis::class, 'result_id');
    }

    public function analysis()
    {
        return $this->hasOneThrough(Analysis::class, Sample::class);
    }

    /**
     * Result Type
     *
     * @return Relationship
     */
    public function category()
    {
        return $this->belongsTo(ResultCategory::class, 'type');
    }

    public function resultable()
    {
        return $this->morphTo();
    }

    /**
     * Unit
     *
     * @return Relationship
     */
    public function unit()
    {
        return $this->belongsTo(Unit::class)->withDefault();
    }


    /**
     * Protocol
     *
     * @return Relationship
     */
    public function protocol()
    {
        return $this->belongsTo(Protocol::class)->withDefault();
    }


    /**
     * Standard
     *
     * @return Relationship
     */
    public function standard()
    {
        return $this->belongsTo(Standard::class)->withDefault();
    }


    /**
     * Normative Work Procedure
     *
     * @return Relationship
     */
    public function nwp()
    {
        return $this->belongsTo(NormativeWorkProcedure::class)->withDefault();
    }

    public function code()
    {
        return $this->belongsTo(LabCode::class, 'code_id');
    }

    public function getAbilities()
    {

        return self::ABILITIES;
    }

    public function isCalculatedParameter(): bool
    {
        return $this->parameter && $this->parameter->requires_calculation;
    }

    public function getCalculationInputs(): array
    {
        return $this->calculation_metadata['inputs'] ?? [];
    }

    public function getFormulaUsed(): ?string
    {
        return $this->calculation_metadata['formula_used'] ?? null;
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('verification_signature')
            ->acceptsMimeTypes(['image/jpeg', 'image/png'])
            ->singleFile();

        $this->addMediaCollection('approval_signature')
            ->acceptsMimeTypes(['image/jpeg', 'image/png'])
            ->singleFile();
    }
}
