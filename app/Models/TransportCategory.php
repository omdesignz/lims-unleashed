<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class TransportCategory extends Model
{
    use HasFactory, SoftDeletes;

    public CONST MENU_NAME = 'trans_types';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'description',
    ];

    protected $table = 'trans_categories';
    protected $dates = ['created_at', 'updated_at', 'deleted_at'];

}
