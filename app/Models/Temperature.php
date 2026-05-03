<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class Temperature extends Model
{
    use HasFactory, SoftDeletes;

    public CONST MENU_NAME = 'temperatures';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'code',
        'description',
    ];

    protected $table = 'temperatures';
    protected $dates = ['created_at', 'updated_at', 'deleted_at'];

}
