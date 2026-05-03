<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MatrixProfile extends Model
{
    use HasFactory, SoftDeletes;

    public CONST MENU_NAME = null;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'matrix_id',
        'profile_id',
    ];

    protected $table = 'matrix_profile';
    protected $dates = ['created_at', 'updated_at', 'deleted_at'];


    /**
     * Matrix
     *
     * @return Relationship
     */
    public function matrix()
    {
        return $this->belongsTo(Matrix::class)->withTrashed();
    } 

    /**
     * Profile
     *
     * @return Relationship
     */
    public function profile()
    {
        return $this->belongsTo(Profile::class)->withTrashed();
    }
}
