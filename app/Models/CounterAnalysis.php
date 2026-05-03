<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\AsCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CounterAnalysis extends Model
{
    use HasFactory, SoftDeletes;

    public const MENU_NAME = 'counter_analysis';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'result_id',
        'analysis_id',
        'department_id',
        'profile_id',
        'parameter_id',
        'sample_id',
        'type_id',
        'cl_id',
        'status',
        'entry_date',
        'init_date',
        'end_date',
        'col_date',
        'user_id',
        'extra_data',
    ];

    protected $table = 'counter_analysis';
    protected $dates = ['created_at', 'updated_at', 'deleted_at', 'entry_date', 'init_date', 'end_date'];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'status' => 'boolean',
        'extra_data' => AsCollection::class
    ];


    /**
     * Result
     *
     * @return Relationship
     */
    public function requested_result()
    {
        return $this->belongsTo(Result::class, 'result_id');
    }

    public function code()
    {
        return $this->belongsTo(LabCode::class, 'cl_id');
    }

    public function codeable()
    {
        return $this->morphOne(LabCode::class, 'codeable');
    }


    /**
     * User
     *
     * @return Relationship
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Result Type
     *
     * @return Relationship
     */
    public function category()
    {
        return $this->belongsTo(ResultType::class, 'type');
    }

    public function result()
    {
        return $this->morphOne(Result::class, 'resultable');
    }

    /**
     * Department
     *
     * @return Relationship
     */
    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Analysis Type
     *
     * @return Relationship
     */
    public function type()
    {
        return $this->belongsTo(AnalysisCategory::class, 'type_id');
    }

    /**
     * Profile
     *
     * @return Relationship
     */
    public function profile()
    {
        return $this->belongsTo(Profile::class);
    }

    /**
     * Parameter
     *
     * @return Relationship
     */
    public function parameter()
    {
        return $this->belongsTo(Parameter::class);
    }

    /**
     * Sample
     *
     * @return Relationship
     */
    public function sample()
    {
        return $this->belongsTo(Sample::class);
    }

    /**
     * Analysis
     *
     * @return Relationship
     */
    public function analysis()
    {
        return $this->belongsTo(Analysis::class);
    }
}
