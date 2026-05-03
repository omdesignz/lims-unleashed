<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Worksheet extends Model
{
    use HasFactory, SoftDeletes;

    public CONST MENU_NAME = 'worksheets';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'worksheets',
        'user_id',
    ];

    protected $table = 'worksheets';
    protected $dates = ['created_at', 'updated_at', 'deleted_at'];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'worksheets' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
