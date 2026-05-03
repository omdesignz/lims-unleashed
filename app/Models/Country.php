<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class Country extends Model
{
    use HasFactory, SoftDeletes;

    public CONST MENU_NAME = 'countries';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'code',
        'name',
        'phone_code',
        'user_id',
    ];

    protected $table = 'countries';
    protected $dates = ['created_at', 'updated_at', 'deleted_at'];

}
