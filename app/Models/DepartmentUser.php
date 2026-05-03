<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DepartmentUser extends Model
{
    use HasFactory, SoftDeletes;

    public CONST MENU_NAME = null;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'department_id',
        'user_id', 
    ];

    protected $table = 'department_user';
    protected $dates = ['created_at', 'updated_at', 'deleted_at'];


    public function department()
    {
      return $this->belongsTo(Department::class, 'department_id');
    }


    public function user()
    {
      return $this->belongsTo(User::class, 'user_id');
    }
}
