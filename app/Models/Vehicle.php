<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class Vehicle extends Model
{
    use HasFactory, SoftDeletes;

    public CONST MENU_NAME = 'vehicles';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'category_id',
        'department_id',
        'number_plate',
        'description',
        'extra_data',
    ];

    protected $table = 'vehicles';
    protected $dates = ['created_at', 'updated_at', 'deleted_at'];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'extra_data' => 'array',
    ];


    /**
     * Department
     *
     * @return Relationship
     */
    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    /**
     * Category
     *
     * @return Relationship
     */
    public function category()
    {
        return $this->belongsTo(TransportCategory::class, 'category_id');
    }
}
