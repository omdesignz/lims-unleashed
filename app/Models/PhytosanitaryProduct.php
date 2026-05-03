<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PhytosanitaryProduct extends Model
{
    use HasFactory, SoftDeletes;

    public CONST MENU_NAME = 'phytosanitary_products';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'description',
    ];

    protected $table = 'phytosanitary_products';
    protected $dates = ['created_at', 'updated_at', 'deleted_at'];
}
