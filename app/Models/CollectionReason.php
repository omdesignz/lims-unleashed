<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class CollectionReason extends Model
{
    use HasFactory, SoftDeletes;

    public CONST MENU_NAME = 'collection_reasons';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'code',
        'description',
        'department_id',
    ];

    protected $table = 'collection_reasons';
    protected $dates = ['created_at', 'updated_at', 'deleted_at'];


    /**
     * Department
     *
     * @return Relationship
     */
    public function department() {
        return $this->belongsTo(Department::class, 'department_id');
    }

}
