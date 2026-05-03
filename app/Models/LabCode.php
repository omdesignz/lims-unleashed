<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use HighSolutions\EloquentSequence\Sequence;

class LabCode extends Model
{
    use HasFactory, Sequence, SoftDeletes;

    public CONST MENU_NAME = null;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'collection_id',
        'code',
        'cl_month',
        'seq',
        'codeable_id',
        'codeable_type',
    ];

    protected $table = 'lab_codes';
    protected $dates = ['created_at', 'updated_at', 'deleted_at'];

    public const STATUS_CODE_NORMAL = 'N';


    public function sequence()
    {
        return [
            'group' => ['cl_month', 'codeable_type'],
            'fieldName' => 'seq',
        ];
    }

    public function collection()
    {
        return $this->belongsTo(CollectionProduct::class, 'collection_id');
    }


    public function samples()
    {
        return $this->hasMany(Sample::class, 'cl_id');
    }

    public function analysis()
    {
        return $this->hasManyThrough(Analysis::class, Sample::class, 'cl_id', 'sample_id', 'id');
    }

    public function completed_analysis()
    {
        return $this->hasManyThrough(Analysis::class, Sample::class, 'cl_id', 'sample_id', 'id')->whereNotNull('end_date');
    }

    public function pending_analysis()
    {
        return $this->hasManyThrough(Analysis::class, Sample::class, 'cl_id', 'sample_id', 'id')->whereNull('init_date');
    }

    public function in_progress_analysis()
    {
        return $this->hasManyThrough(Analysis::class, Sample::class, 'cl_id', 'sample_id', 'id')->whereNull('end_date')->whereNotNull('init_date');
    }

    public function results()
    {
        return $this->hasManyThrough(Result::class, Sample::class, 'cl_id', 'sample_id', 'id');
    }

    public function latest_inserted_result()
    {
        return $this->hasManyThrough(Result::class, Sample::class, 'cl_id', 'sample_id', 'id')->whereNotNull('inserted_date')->latest() ?? [];
    }

    public function latest_verified_result()
    {
        return $this->hasManyThrough(Result::class, Sample::class, 'cl_id', 'sample_id', 'id')->whereNotNull('verified_date')->latest() ?? [];
    }

    public function latest_approved_result()
    {
        return $this->hasManyThrough(Result::class, Sample::class, 'cl_id', 'sample_id', 'id')->whereNotNull('approved_date')->latest() ?? [];
    }

    public function quality_certificate() {
        return $this->hasManyThrough(QualityCertificate::class, CollectionProduct::class, 'collection_id', 'collection_id', 'id');
    }

    public function codeable()
    {
      return $this->morphTo();
    }

    public static function boot()
    {
        parent::boot();

            static::creating(function($cl) {

                $cl->seq += 1;
                $cl->code = $cl->cl_month . '/' . str_pad ($cl->seq, 4, '0', STR_PAD_LEFT);
        
            });

    }
}
